<template>
  <q-page padding>
    <div class="text-h4 q-mb-md">Transferir</div>

    <q-card>
      <q-card-section>
        <q-form @submit="onSubmit" class="q-gutter-md">
          <div class="text-subtitle2 q-mb-sm">Saldo Disponível: {{ formatCurrency(wallet?.balance || 0) }}</div>

          <q-input
            v-model="form.destination"
            label="Destino (Código NDL, E-mail ou Telefone)"
            :rules="[val => !!val || 'Destino é obrigatório']"
            outlined
            hint="Digite o código NDL, e-mail ou telefone do destinatário"
          />

          <q-input
            v-model.number="form.amount"
            label="Valor"
            type="number"
            step="0.01"
            min="0.01"
            :max="wallet?.balance || 0"
            :rules="[
              val => !!val || 'Valor é obrigatório',
              val => val > 0 || 'Valor deve ser maior que zero',
              val => val <= (wallet?.balance || 0) || 'Saldo insuficiente'
            ]"
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

          <div>
            <q-btn
              label="Transferir"
              type="submit"
              color="primary"
              class="full-width"
              :loading="loading"
              :disable="!wallet || wallet.balance <= 0"
            />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useQuasar } from 'quasar';
import axios from 'axios';

const router = useRouter();
const authStore = useAuthStore();
const $q = useQuasar();

const wallet = ref(null);
const loading = ref(false);

const form = ref({
  destination: '',
  amount: null,
  description: ''
});

async function loadWallet() {
  try {
    const response = await axios.get('/client/transfer');
    wallet.value = response.data.wallet;
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar informações da carteira'
    });
  }
}

async function onSubmit() {
  loading.value = true;
  try {
    await axios.post('/client/transfer', form.value);
    $q.notify({
      type: 'positive',
      message: 'Transferência realizada com sucesso!'
    });
    router.push({ name: 'client.dashboard' });
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Erro ao realizar transferência'
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
  loadWallet();
});
</script>
