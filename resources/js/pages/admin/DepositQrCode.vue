<template>
  <q-page padding>
    <div class="row items-center q-mb-md">
      <q-btn flat icon="arrow_back" @click="$router.back()" />
      <div class="text-h4 q-ml-sm">Gerar QR Code de Depósito</div>
    </div>

    <q-card>
      <q-card-section>
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
            :rules="form.hasAmount ? [val => !!val || 'Valor é obrigatório'] : []"
            outlined
            prefix="NDL"
          />

          <q-input
            v-model="form.description"
            label="Descrição (opcional)"
            outlined
            type="textarea"
            rows="2"
          />

          <q-input
            v-model="form.expires_at"
            label="Data de Expiração (opcional)"
            type="datetime-local"
            outlined
            hint="Deixe em branco para não expirar"
          />

          <q-input
            v-model.number="form.usage_limit"
            label="Limite de Uso"
            type="number"
            min="1"
            outlined
            hint="Quantas vezes este QR Code pode ser usado (padrão: 1)"
          />

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

const form = ref({
  hasAmount: false,
  amount: null,
  description: '',
  expires_at: null,
  usage_limit: 1
});

async function onSubmit() {
  loading.value = true;
  try {
    const data = {
      amount: form.value.hasAmount ? form.value.amount : null,
      description: form.value.description || null,
      expires_at: form.value.expires_at || null,
      usage_limit: form.value.usage_limit || 1
    };

    const response = await axios.post('/admin/deposits/qrcode', data);
    qrCodeImage.value = response.data.qr_code_image;
    qrCodeUrl.value = response.data.url;
    showQrCode.value = true;
    
    $q.notify({
      type: 'positive',
      message: 'QR Code gerado com sucesso!'
    });
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Erro ao gerar QR Code'
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
</script>

