<template>
  <q-page padding>
    <div class="text-h4 q-mb-md">Extrato</div>

    <!-- Filtros -->
    <q-card class="q-mb-md">
      <q-card-section>
        <div class="row q-gutter-md">
          <div class="col-12 col-md-4">
            <q-select
              v-model="filters.type"
              :options="typeOptions"
              option-label="label"
              option-value="value"
              label="Tipo"
              clearable
              outlined
              dense
              emit-value
              map-options
            />
          </div>
          <div class="col-12 col-md-4">
            <q-input
              v-model="filters.date_from"
              label="Data Inicial"
              type="date"
              outlined
              dense
            />
          </div>
          <div class="col-12 col-md-4">
            <q-input
              v-model="filters.date_to"
              label="Data Final"
              type="date"
              outlined
              dense
            />
          </div>
        </div>
        <div class="row q-mt-md">
          <q-btn
            color="primary"
            label="Filtrar"
            @click="loadTransactions"
          />
          <q-btn
            flat
            label="Limpar"
            class="q-ml-sm"
            @click="clearFilters"
          />
        </div>
      </q-card-section>
    </q-card>

    <!-- Lista de Transações -->
    <q-card>
      <q-card-section>
        <div class="text-h6 q-mb-md">Transações</div>
        
        <q-list v-if="transactions.length > 0" separator>
          <q-item
            v-for="transaction in transactions"
            :key="transaction.id"
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
              <q-item-label caption>
                {{ formatDate(transaction.created_at) }}
                <q-chip
                  :color="getStatusColor(transaction.status)"
                  text-color="white"
                  size="sm"
                  class="q-ml-xs"
                >
                  {{ transaction.status }}
                </q-chip>
              </q-item-label>
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

        <div v-else-if="!loading" class="text-center q-pa-md text-grey-6">
          Nenhuma transação encontrada
        </div>

        <q-inner-loading :showing="loading" />

        <!-- Paginação -->
        <div v-if="pagination && pagination.last_page > 1" class="row justify-center q-mt-md">
          <q-pagination
            v-model="pagination.current_page"
            :max="pagination.last_page"
            :max-pages="5"
            direction-links
            @update:model-value="(page) => loadTransactions(page)"
          />
        </div>
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

const transactions = ref([]);
const loading = ref(false);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0
});
const wallet = ref(null);

const filters = ref({
  type: null,
  date_from: null,
  date_to: null
});

const typeOptions = [
  { label: 'Transferência', value: 'transfer' },
  { label: 'Pagamento', value: 'payment' },
  { label: 'Depósito', value: 'deposit' }
];

async function loadTransactions(page = null) {
  loading.value = true;
  try {
    // Se page for null, usar a página atual ou 1
    const currentPage = page || pagination.value?.current_page || 1;
    
    const params = {
      page: currentPage,
      ...filters.value
    };
    
    // Remover valores nulos e undefined
    Object.keys(params).forEach(key => {
      if (params[key] === null || params[key] === '' || params[key] === undefined) {
        delete params[key];
      }
    });

    const response = await axios.get('/client/transactions', { params });
    
    if (response.data && response.data.data) {
      transactions.value = response.data.data;
      pagination.value = {
        current_page: response.data.current_page || 1,
        last_page: response.data.last_page || 1,
        per_page: response.data.per_page || 20,
        total: response.data.total || 0
      };
    } else {
      // Fallback caso a estrutura seja diferente
      transactions.value = Array.isArray(response.data) ? response.data : [];
      pagination.value = {
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: transactions.value.length
      };
    }
  } catch (error) {
    console.error('Erro ao carregar transações:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Erro ao carregar transações'
    });
    transactions.value = [];
  } finally {
    loading.value = false;
  }
}

function clearFilters() {
  filters.value = {
    type: null,
    date_from: null,
    date_to: null
  };
  loadTransactions();
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
  if (!wallet.value) {
    return 'account_balance_wallet';
  }
  
  if (transaction.type === 'transfer') {
    return transaction.from_wallet_id === wallet.value.id ? 'arrow_upward' : 'arrow_downward';
  }
  if (transaction.type === 'payment') {
    return 'payment';
  }
  return 'account_balance_wallet';
}

function getTransactionColor(transaction) {
  if (!wallet.value) {
    return 'primary';
  }
  
  if (transaction.to_wallet_id === wallet.value.id) {
    return 'positive';
  }
  return 'negative';
}

function getTransactionDescription(transaction) {
  if (!wallet.value) {
    if (transaction.type === 'transfer') {
      return `Transferência: ${transaction.from_user?.name || 'N/A'} → ${transaction.to_user?.name || 'N/A'}`;
    }
    if (transaction.type === 'payment') {
      return `Pagamento: ${transaction.from_user?.name || 'N/A'} → ${transaction.to_user?.name || 'N/A'}`;
    }
    return transaction.description || 'Depósito';
  }
  
  const isOutgoing = transaction.from_wallet_id === wallet.value.id;
  
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
  if (!wallet.value) {
    return '';
  }
  return transaction.to_wallet_id === wallet.value.id ? '+' : '-';
}

function getAmountClass(transaction) {
  if (!wallet.value) {
    return 'text-weight-bold';
  }
  return transaction.to_wallet_id === wallet.value.id
    ? 'text-positive text-weight-bold'
    : 'text-negative text-weight-bold';
}

function getStatusColor(status) {
  const colors = {
    completed: 'positive',
    pending: 'warning',
    cancelled: 'grey',
    failed: 'negative'
  };
  return colors[status] || 'grey';
}

onMounted(async () => {
  // Carregar wallet primeiro
  try {
    const dashboardResponse = await axios.get('/client/dashboard');
    wallet.value = dashboardResponse.data.wallet;
  } catch (error) {
    console.error('Erro ao carregar wallet:', error);
    // Tentar carregar apenas a wallet
    try {
      const transferResponse = await axios.get('/client/transfer');
      wallet.value = transferResponse.data.wallet;
    } catch (err) {
      console.error('Erro ao carregar wallet do transfer:', err);
    }
  }
  
  loadTransactions();
});
</script>
