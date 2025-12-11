<template>
  <q-page padding>
    <div class="row items-center q-mb-md">
      <q-btn flat icon="arrow_back" @click="$router.back()" />
      <div class="text-h4 q-ml-sm">Gerar QR Code de Pagamento</div>
    </div>

    <q-card>
      <q-card-section>
        <div class="text-body2 text-grey-7 q-mb-md">
          Gere um QR Code de pagamento que direciona pagamentos para a carteira do sistema. 
          Este QR Code pode ser usado ilimitadamente por diferentes clientes.
        </div>
        <q-form @submit="onSubmit" class="q-gutter-md">
          <q-toggle
            v-model="form.hasAmount"
            label="Valor fixo"
            @update:model-value="onToggleAmount"
          />

          <q-input
            v-if="form.hasAmount"
            v-model.number="form.amount"
            label="Valor"
            type="number"
            step="0.01"
            min="0.01"
            :rules="form.hasAmount ? [val => !!val || 'Valor é obrigatório', val => val > 0 || 'Valor deve ser maior que zero'] : []"
            outlined
            prefix="NDL"
          />

          <q-input
            v-model="form.description"
            label="Descrição (opcional)"
            outlined
            type="textarea"
            rows="2"
            hint="Ex: Compra de produto X, Pagamento de serviço Y"
          />

          <q-input
            v-model="form.expires_at"
            label="Data de Expiração (opcional)"
            type="datetime-local"
            outlined
            hint="Deixe em branco para não expirar"
          />

          <div class="text-caption text-grey-7 q-mt-sm">
            <q-icon name="info" size="sm" class="q-mr-xs" />
            Este QR Code pode ser usado ilimitadamente por diferentes clientes.
          </div>

          <div>
            <q-btn
              label="Gerar QR Code"
              type="submit"
              color="primary"
              class="full-width"
              :loading="loading"
            />
          </div>
        </q-form>
      </q-card-section>
    </q-card>

    <!-- QR Code Gerado -->
    <q-dialog v-model="showQrCode" persistent>
      <q-card style="min-width: 300px">
        <q-card-section>
          <div class="text-h6">QR Code Gerado</div>
        </q-card-section>

        <q-card-section class="q-pt-none text-center">
          <img
            v-if="qrCodeImage"
            :src="qrCodeImage"
            alt="QR Code"
            style="max-width: 100%; height: auto;"
          />
          <div class="q-mt-md text-caption text-grey-7">
            {{ qrCodeUrl }}
          </div>
          <div v-if="qrCodeData" class="q-mt-sm">
            <div class="text-caption">
              <strong>Valor:</strong> {{ qrCodeData.amount ? formatCurrency(qrCodeData.amount) : 'Variável' }}
            </div>
            <div v-if="qrCodeData.description" class="text-caption">
              <strong>Descrição:</strong> {{ qrCodeData.description }}
            </div>
            <div v-if="qrCodeData.expires_at" class="text-caption">
              <strong>Expira em:</strong> {{ formatDate(qrCodeData.expires_at) }}
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Fechar" color="primary" @click="showQrCode = false" />
          <q-btn flat label="Copiar Link" color="primary" @click="copyUrl" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref } from 'vue';
import { useQuasar } from 'quasar';
import axios from 'axios';

const $q = useQuasar();

const loading = ref(false);
const showQrCode = ref(false);
const qrCodeImage = ref(null);
const qrCodeUrl = ref('');
const qrCodeData = ref(null);

const form = ref({
  hasAmount: false,
  amount: null,
  description: '',
  expires_at: null
});

async function onSubmit() {
  loading.value = true;
  try {
    const data = {
      amount: form.value.hasAmount ? form.value.amount : null,
      description: form.value.description || null,
      expires_at: form.value.expires_at || null
    };

    const response = await axios.post('/admin/payments/qrcode', data);
    qrCodeImage.value = response.data.qr_code_image;
    qrCodeUrl.value = response.data.url;
    qrCodeData.value = response.data.qr_code;
    showQrCode.value = true;
    
    $q.notify({
      type: 'positive',
      message: 'QR Code gerado com sucesso!'
    });
  } catch (error) {
    console.error('Erro ao gerar QR Code:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Erro ao gerar QR Code',
      caption: error.response?.data?.errors ? Object.values(error.response.data.errors).flat().join(', ') : ''
    });
  } finally {
    loading.value = false;
  }
}

function onToggleAmount(value) {
  if (!value) {
    form.value.amount = null;
  }
}

function copyUrl() {
  navigator.clipboard.writeText(qrCodeUrl.value).then(() => {
    $q.notify({
      type: 'positive',
      message: 'Link copiado para a área de transferência!'
    });
  });
}

function formatCurrency(value) {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(value).replace('R$', 'NDL');
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}
</script>

