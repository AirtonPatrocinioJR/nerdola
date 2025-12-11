<template>
  <q-page padding class="flex flex-center">
    <q-card v-if="qrCode" style="min-width: 350px; max-width: 500px">
      <q-card-section>
        <div class="text-h6 text-center">Confirmar Pagamento</div>
      </q-card-section>

      <q-card-section>
        <div class="text-center q-mb-md">
          <div class="text-subtitle2 text-grey-7">Para</div>
          <div class="text-h6">{{ qrCode.user?.name }}</div>
        </div>

        <q-input
          v-model.number="form.amount"
          :label="qrCode.amount ? `Valor (fixo: ${formatCurrency(qrCode.amount)})` : 'Valor'"
          type="number"
          step="0.01"
          min="0.01"
          :value="qrCode.amount || form.amount"
          :readonly="!!qrCode.amount"
          :rules="[val => !!val || 'Valor é obrigatório']"
          outlined
          prefix="NDL"
          class="q-mb-md"
        />

        <div v-if="qrCode.description" class="q-mb-md">
          <div class="text-caption text-grey-7">Descrição</div>
          <div>{{ qrCode.description }}</div>
        </div>

        <q-btn
          label="Confirmar Pagamento"
          color="primary"
          class="full-width"
          :loading="loading"
          @click="confirmPayment"
        />
      </q-card-section>
    </q-card>

    <q-inner-loading :showing="loadingQrCode" />
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useQuasar } from 'quasar';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const $q = useQuasar();

const qrCode = ref(null);
const loading = ref(false);
const loadingQrCode = ref(false);

const form = ref({
  amount: null
});

async function loadQrCode() {
  loadingQrCode.value = true;
  try {
    const response = await axios.get(`/qr/pay/${route.params.token}`);
    qrCode.value = response.data.qr_code;
    form.value.amount = qrCode.value.amount || null;
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'QR Code inválido ou expirado'
    });
    router.push({ name: 'login' });
  } finally {
    loadingQrCode.value = false;
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
      router.push({ name: 'login', query: { redirect: route.fullPath } });
    });
    return;
  }

  loading.value = true;
  try {
    await axios.post(`/qr/pay/${route.params.token}/confirm`, {
      amount: form.value.amount
    });
    $q.notify({
      type: 'positive',
      message: 'Pagamento realizado com sucesso!'
    });
    router.push({ name: 'client.dashboard' });
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Erro ao processar pagamento'
    });
  } finally {
    loading.value = false;
  }
}

function formatCurrency(value) {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(value).replace('R$', 'NDL');
}

onMounted(() => {
  loadQrCode();
});
</script>
