<template>
  <q-page padding>
    <div class="q-mb-lg">
      <div class="text-h4 q-mb-sm">Dashboard</div>
      <div class="text-subtitle2 text-grey-7">Bem-vindo, {{ authStore.user?.name }}!</div>
    </div>

    <!-- Saldo -->
    <q-card class="q-mb-md bg-primary text-white">
      <q-card-section>
        <div class="text-subtitle2 q-mb-xs">Saldo Disponível</div>
        <div class="text-h3">{{ formatCurrency(wallet?.balance || 0) }}</div>
        <div class="text-caption q-mt-xs">Código: {{ wallet?.code }}</div>
      </q-card-section>
    </q-card>

    <!-- Acesso Rápido -->
    <div class="row q-gutter-md q-mb-md">
      <div class="col-6">
        <q-btn
          unelevated
          color="primary"
          class="full-width"
          icon="send"
          label="Transferir"
          @click="$router.push({ name: 'client.transfer' })"
        />
      </div>
      <div class="col-6">
        <q-btn
          unelevated
          color="secondary"
          class="full-width"
          icon="qr_code"
          label="QR Code"
          @click="$router.push({ name: 'client.qrcode.form' })"
        />
      </div>
    </div>

    <!-- Últimas Transações -->
    <q-card>
      <q-card-section>
        <div class="text-h6 q-mb-md">Últimas Transações</div>
        
        <q-list v-if="transactions.length > 0" separator>
          <q-item
            v-for="transaction in transactions"
            :key="transaction.id"
            clickable
            @click="$router.push({ name: 'client.transactions' })"
          >
            <q-item-section avatar>
              <q-icon
                :name="getTransactionIcon(transaction)"
                :color="getTransactionColor(transaction)"
                size="md"
              />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ getTransactionDescription(transaction) }}</q-item-label>
              <q-item-label caption>{{ formatDate(transaction.created_at) }}</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-item-label
                :class="getAmountClass(transaction)"
              >
                {{ getAmountSign(transaction) }}{{ formatCurrency(transaction.amount) }}
              </q-item-label>
            </q-item-section>
          </q-item>
        </q-list>

        <div v-else class="text-center q-pa-md text-grey-6">
          Nenhuma transação encontrada
        </div>

        <q-btn
          v-if="transactions.length > 0"
          flat
          color="primary"
          label="Ver todas"
          class="full-width q-mt-md"
          @click="$router.push({ name: 'client.transactions' })"
        />
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useQuasar } from 'quasar';
import axios from 'axios';

const authStore = useAuthStore();
const $q = useQuasar();

const wallet = ref(null);
const transactions = ref([]);
const loading = ref(false);

async function loadDashboard() {
  loading.value = true;
  try {
    const response = await axios.get('/client/dashboard');
    wallet.value = response.data.wallet;
    transactions.value = response.data.transactions;
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar dashboard'
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

function formatDate(date) {
  return new Date(date).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function getTransactionIcon(transaction) {
  if (transaction.type === 'transfer') {
    return transaction.from_wallet_id === wallet.value?.id ? 'arrow_upward' : 'arrow_downward';
  }
  if (transaction.type === 'payment') {
    return transaction.from_wallet_id === wallet.value?.id ? 'payment' : 'qr_code';
  }
  return 'account_balance_wallet';
}

function getTransactionColor(transaction) {
  if (transaction.to_wallet_id === wallet.value?.id) {
    return 'positive';
  }
  return 'negative';
}

function getTransactionDescription(transaction) {
  const isOutgoing = transaction.from_wallet_id === wallet.value?.id;
  
  if (transaction.type === 'transfer') {
    return isOutgoing
      ? `Transferência para ${transaction.to_user?.name || 'Usuário'}`
      : `Transferência de ${transaction.from_user?.name || 'Usuário'}`;
  }
  
  if (transaction.type === 'payment') {
    return isOutgoing
      ? `Pagamento para ${transaction.to_user?.name || 'Usuário'}`
      : `Recebimento de ${transaction.from_user?.name || 'Usuário'}`;
  }
  
  return transaction.description || 'Depósito';
}

function getAmountSign(transaction) {
  return transaction.to_wallet_id === wallet.value?.id ? '+' : '-';
}

function getAmountClass(transaction) {
  return transaction.to_wallet_id === wallet.value?.id
    ? 'text-positive text-weight-bold'
    : 'text-negative text-weight-bold';
}

onMounted(() => {
  loadDashboard();
});
</script>
