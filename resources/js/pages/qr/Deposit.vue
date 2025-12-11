<template>
  <q-page padding class="flex flex-center">
    <q-card v-if="qrCode" style="min-width: 350px; max-width: 500px">
      <q-card-section>
        <div class="text-h6 text-center">Confirmar Depósito</div>
      </q-card-section>

      <q-card-section>
        <q-form @submit="confirmDeposit" class="q-gutter-md">
          <q-select
            v-model="form.user_id"
            :options="users"
            option-label="name"
            option-value="id"
            label="Cliente"
            :rules="[val => !!val || 'Cliente é obrigatório']"
            outlined
            emit-value
            map-options
          />

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
          />

          <div v-if="qrCode.description" class="q-mb-md">
            <div class="text-caption text-grey-7">Descrição</div>
            <div>{{ qrCode.description }}</div>
          </div>

          <q-btn
            label="Confirmar Depósito"
            type="submit"
            color="primary"
            class="full-width"
            :loading="loading"
          />
        </q-form>
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
const users = ref([]);
const loading = ref(false);
const loadingQrCode = ref(false);

const form = ref({
  user_id: null,
  amount: null
});

async function loadQrCode() {
  loadingQrCode.value = true;
  try {
    const response = await axios.get(`/qr/deposit/${route.params.token}`);
    qrCode.value = response.data.qr_code;
    form.value.amount = qrCode.value.amount || null;
    
    // Carregar usuários se for admin
    if (authStore.isAuthenticated && authStore.user?.role === 'admin') {
      const usersResponse = await axios.get('/admin/deposits/create');
      users.value = usersResponse.data.users;
    }
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

async function confirmDeposit() {
  if (!authStore.isAuthenticated || authStore.user?.role !== 'admin') {
    $q.dialog({
      title: 'Acesso Negado',
      message: 'Apenas administradores podem processar depósitos. Deseja fazer login?',
      cancel: true,
      persistent: true
    }).onOk(() => {
      router.push({ name: 'login', query: { redirect: route.fullPath } });
    });
    return;
  }

  loading.value = true;
  try {
    await axios.post(`/qr/deposit/${route.params.token}/confirm`, form.value);
    $q.notify({
      type: 'positive',
      message: 'Depósito realizado com sucesso!'
    });
    router.push({ name: 'admin.deposits' });
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Erro ao processar depósito'
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
