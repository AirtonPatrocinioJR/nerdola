<template>
  <q-page padding>
    <div class="text-h4 q-mb-md">Ler QR Code de Pagamento</div>

    <q-card>
      <q-card-section>
        <div class="row q-gutter-md">
          <!-- Opção de Câmera -->
          <div class="col-12">
            <q-btn
              v-if="!cameraActive"
              color="primary"
              icon="camera_alt"
              label="Usar Câmera"
              class="full-width"
              @click="startCamera"
            />
            <q-btn
              v-else
              color="negative"
              icon="stop"
              label="Parar Câmera"
              class="full-width"
              @click="stopCamera"
            />
          </div>

          <!-- Área da Câmera -->
          <div v-if="cameraActive" class="col-12">
            <div class="text-center q-mb-sm">
              <div class="text-caption text-grey-7">
                Posicione o QR Code na frente da câmera
              </div>
            </div>
            <div class="relative-position" style="max-width: 100%; margin: 0 auto;">
              <video
                ref="videoElement"
                autoplay
                playsinline
                muted
                style="width: 100%; max-width: 500px; border-radius: 8px; background: #000;"
              />
              <div
                v-if="scanning"
                class="absolute-full flex flex-center"
                style="background: rgba(0,0,0,0.3); border-radius: 8px;"
              >
                <q-spinner color="white" size="3em" />
              </div>
            </div>
          </div>

        </div>
      </q-card-section>
    </q-card>

    <!-- Dialog de Confirmação -->
    <q-dialog v-model="showConfirmDialog" persistent>
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">
            {{ qrCodeData?.type === 'deposit' ? 'Confirmar Depósito' : 'Confirmar Pagamento' }}
          </div>
        </q-card-section>

        <q-card-section v-if="qrCodeData">
          <div class="q-mb-md">
            <div class="text-subtitle2 text-grey-7">Para</div>
            <div class="text-h6">{{ qrCodeData.user?.name || 'Carregando...' }}</div>
          </div>

          <q-input
            v-model.number="paymentAmount"
            :label="qrCodeData.amount ? `Valor (fixo: ${formatCurrency(qrCodeData.amount)})` : 'Valor'"
            type="number"
            step="0.01"
            min="0.01"
            :value="qrCodeData.amount || paymentAmount"
            :readonly="!!qrCodeData.amount"
            :rules="[val => !!val || 'Valor é obrigatório']"
            outlined
            prefix="NDL"
            class="q-mb-md"
          />

          <div v-if="qrCodeData.description" class="q-mb-md">
            <div class="text-caption text-grey-7">Descrição</div>
            <div>{{ qrCodeData.description }}</div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="negative" @click="cancelPayment" />
          <q-btn
            flat
            :label="qrCodeData?.type === 'deposit' ? 'Receber Depósito' : 'Confirmar Pagamento'"
            color="primary"
            :loading="processingPayment"
            @click="confirmPayment"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onUnmounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useQuasar } from 'quasar';
import axios from 'axios';
import { BrowserMultiFormatReader } from '@zxing/library';

const router = useRouter();
const authStore = useAuthStore();
const $q = useQuasar();

const videoElement = ref(null);
const cameraActive = ref(false);
const scanning = ref(false);
const showConfirmDialog = ref(false);
const qrCodeData = ref(null);
const paymentAmount = ref(null);
const processingPayment = ref(false);

let stream = null;
let codeReader = null;
let scanInterval = null;

async function startCamera() {
  try {
    // Primeiro, ativar a câmera para renderizar o elemento de vídeo
    cameraActive.value = true;
    
    // Aguardar o próximo tick do Vue para garantir que o elemento está no DOM
    await nextTick();
    
    // Aguardar um pouco mais para garantir que o elemento está totalmente renderizado
    await new Promise(resolve => setTimeout(resolve, 200));
    
    if (!videoElement.value) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao acessar o elemento de vídeo'
      });
      cameraActive.value = false;
      return;
    }

    codeReader = new BrowserMultiFormatReader();
    
    const videoInputDevices = await codeReader.listVideoInputDevices();
    
    if (videoInputDevices.length === 0) {
      $q.notify({
        type: 'negative',
        message: 'Nenhuma câmera encontrada'
      });
      return;
    }

    const deviceId = videoInputDevices[0].deviceId;
    scanning.value = true;

    // Decodificar continuamente - NotFoundException é esperado quando não há QR Code
    const decodePromise = codeReader.decodeFromVideoDevice(deviceId, videoElement.value, (result, err) => {
        if (result) {
          handleQrCodeScanned(result.getText());
          return;
        }
        
        // NotFoundException é esperado e normal durante a leitura contínua
        // Não fazer nada quando não encontra QR Code - isso é comportamento esperado
        if (err) {
          const errorName = err.name || err.constructor?.name || '';
          // Ignorar todos os tipos de "não encontrado" - são esperados durante leitura contínua
          if (errorName.includes('NotFound') || 
              errorName.includes('NoQRCode') ||
              errorName === 'ChecksumException' ||
              errorName === 'FormatException') {
            // Erro esperado - não fazer nada
            return;
          }
          // Apenas logar erros que não são esperados durante a leitura normal
          console.warn('Aviso ao ler QR Code:', err);
        }
      });
      
      // Tratar erros não capturados no callback
      if (decodePromise && typeof decodePromise.catch === 'function') {
        decodePromise.catch((err) => {
          // Ignorar NotFoundException - é esperado durante leitura contínua
          if (err) {
            const errorName = err.name || err.constructor?.name || '';
            // Ignorar todos os tipos de "não encontrado"
            if (errorName.includes('NotFound') || errorName.includes('NoQRCode')) {
              return; // Erro esperado - ignorar
            }
            // Erro real - tratar
            console.error('Erro ao iniciar leitura:', err);
            $q.notify({
              type: 'negative',
              message: 'Erro ao iniciar a leitura do QR Code'
            });
            stopCamera();
          }
        });
      }
    } catch (err) {
      console.error('Erro ao acessar câmera:', err);
      $q.notify({
        type: 'negative',
        message: err.message || 'Erro ao acessar a câmera. Verifique as permissões.'
      });
      cameraActive.value = false;
      scanning.value = false;
    }
}

function stopCamera() {
  if (codeReader) {
    codeReader.reset();
    codeReader = null;
  }
  if (stream) {
    stream.getTracks().forEach(track => track.stop());
    stream = null;
  }
  cameraActive.value = false;
  scanning.value = false;
}

async function handleQrCodeScanned(text) {
  // Parar a câmera se estiver ativa
  if (cameraActive.value) {
    stopCamera();
  }

  // Extrair token e tipo da URL
  let token = null;
  let qrType = null; // 'pay' ou 'deposit'
  
  try {
    const url = new URL(text);
    const pathParts = url.pathname.split('/').filter(p => p); // Remove strings vazias
    const qrIndex = pathParts.indexOf('qr');
    
    if (qrIndex !== -1 && qrIndex + 1 < pathParts.length) {
      qrType = pathParts[qrIndex + 1]; // 'pay' ou 'deposit'
      if (qrIndex + 2 < pathParts.length) {
        token = pathParts[qrIndex + 2];
      }
    }
  } catch (e) {
    // Se não for uma URL válida, tentar usar o texto diretamente como token
    // Mas isso não é recomendado, então vamos informar o usuário
    $q.notify({
      type: 'negative',
      message: 'QR Code inválido. Certifique-se de que é um QR Code válido do Nerdola Bank.'
    });
    return;
  }

  if (!token || !qrType) {
    $q.notify({
      type: 'negative',
      message: 'QR Code inválido. Certifique-se de que é um QR Code válido do Nerdola Bank.'
    });
    return;
  }

  // Se for depósito, processar como depósito (cliente recebe o dinheiro)
  if (qrType === 'deposit') {
    await handleDepositQrCode(token);
    return;
  }

  // Se não for 'pay', também não é válido para clientes
  if (qrType !== 'pay') {
    $q.notify({
      type: 'negative',
      message: 'Tipo de QR Code não suportado. Use um QR Code de pagamento.'
    });
    return;
  }

  // Carregar dados do QR Code de pagamento
  try {
    const response = await axios.get(`/qr/pay/${token}`);
    qrCodeData.value = {
      ...response.data.qr_code,
      type: 'payment' // Garantir que o tipo está definido
    };
    paymentAmount.value = qrCodeData.value.amount || null;
    showConfirmDialog.value = true;
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'QR Code inválido ou expirado'
    });
  }
}

function cancelPayment() {
  showConfirmDialog.value = false;
  qrCodeData.value = null;
  paymentAmount.value = null;
}

async function handleDepositQrCode(token) {
  if (!authStore.isAuthenticated) {
    $q.dialog({
      title: 'Login Necessário',
      message: 'Você precisa estar logado para receber o depósito. Deseja fazer login?',
      cancel: true,
      persistent: true
    }).onOk(() => {
      router.push({ name: 'login' });
    });
    return;
  }

  // Verificar se o usuário é cliente
  if (authStore.user?.role !== 'client') {
    $q.notify({
      type: 'warning',
      message: 'Apenas clientes podem receber depósitos via QR Code.'
    });
    return;
  }

  // Carregar dados do QR Code de depósito
  try {
    const response = await axios.get(`/qr/deposit/${token}`);
    qrCodeData.value = {
      ...response.data.qr_code,
      type: 'deposit' // Garantir que o tipo está definido
    };
    paymentAmount.value = qrCodeData.value.amount || null;
    
    // Para depósitos, o cliente recebe o dinheiro na própria conta
    // Não precisa selecionar outro cliente
    showConfirmDialog.value = true;
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'QR Code inválido ou expirado'
    });
  }
}

async function confirmPayment() {
  if (!authStore.isAuthenticated) {
    $q.dialog({
      title: 'Login Necessário',
      message: 'Você precisa estar logado para realizar o pagamento. Deseja fazer login?',
      cancel: true,
      persistent: true
    }).onOk(() => {
      router.push({ name: 'login' });
    });
    return;
  }

  // Verificar se é depósito ou pagamento
  const isDeposit = qrCodeData.value?.type === 'deposit';
  
  processingPayment.value = true;
  try {
    if (!qrCodeData.value || !qrCodeData.value.token) {
      throw new Error('Token do QR Code não encontrado');
    }
    
    if (isDeposit) {
      // Confirmar depósito - cliente recebe o dinheiro
      await axios.post(`/qr/deposit/${qrCodeData.value.token}/confirm`, {
        amount: paymentAmount.value,
        user_id: authStore.user.id // Cliente recebe na própria conta
      });
      
      $q.notify({
        type: 'positive',
        message: 'Depósito recebido com sucesso!'
      });
    } else {
      // Confirmar pagamento - cliente paga
      await axios.post(`/qr/pay/${qrCodeData.value.token}/confirm`, {
        amount: paymentAmount.value
      });
      
      $q.notify({
        type: 'positive',
        message: 'Pagamento realizado com sucesso!'
      });
    }
    
    showConfirmDialog.value = false;
    router.push({ name: 'client.dashboard' });
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || (isDeposit ? 'Erro ao processar depósito' : 'Erro ao processar pagamento')
    });
  } finally {
    processingPayment.value = false;
  }
}

function formatCurrency(value) {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(value).replace('R$', 'NDL');
}

onUnmounted(() => {
  stopCamera();
});
</script>

