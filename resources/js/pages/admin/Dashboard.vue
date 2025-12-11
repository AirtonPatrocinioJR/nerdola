<template>
  <q-page padding>
    <div class="text-h4 q-mb-md">Dashboard Admin</div>

    <!-- Estatísticas -->
    <div class="row q-gutter-md q-mb-md">
      <div class="col-12 col-md-6 col-lg-3">
        <q-card class="bg-primary text-white">
          <q-card-section>
            <div class="text-subtitle2">Total de Usuários</div>
            <div class="text-h4">{{ stats?.total_users || 0 }}</div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
        <q-card class="bg-positive text-white">
          <q-card-section>
            <div class="text-subtitle2">Usuários Ativos</div>
            <div class="text-h4">{{ stats?.active_users || 0 }}</div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
        <q-card class="bg-negative text-white">
          <q-card-section>
            <div class="text-subtitle2">Usuários Bloqueados</div>
            <div class="text-h4">{{ stats?.blocked_users || 0 }}</div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
        <q-card class="bg-info text-white">
          <q-card-section>
            <div class="text-subtitle2">Volume Total</div>
            <div class="text-h4">{{ formatCurrency(stats?.total_volume || 0) }}</div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <div class="row q-gutter-md q-mb-md">
      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <div class="text-subtitle2">Transações Hoje</div>
            <div class="text-h5">{{ stats?.today_transactions || 0 }}</div>
            <div class="text-caption text-grey-7">
              Volume: {{ formatCurrency(stats?.today_volume || 0) }}
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <div class="text-subtitle2">Total de Transações</div>
            <div class="text-h5">{{ stats?.total_transactions || 0 }}</div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Transações Recentes -->
    <q-card>
      <q-card-section>
        <div class="text-h6 q-mb-md">Transações Recentes</div>
        
        <q-list v-if="recentTransactions.length > 0" separator>
          <q-item
            v-for="transaction in recentTransactions"
            :key="transaction.id"
          >
            <q-item-section avatar>
              <q-icon
                :name="getTransactionIcon(transaction.type)"
                color="primary"
                size="md"
              />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ getTransactionDescription(transaction) }}</q-item-label>
              <q-item-label caption>{{ formatDate(transaction.created_at) }}</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-item-label class="text-weight-bold">
                {{ formatCurrency(transaction.amount) }}
              </q-item-label>
              <q-chip
                :color="getStatusColor(transaction.status)"
                text-color="white"
                size="sm"
              >
                {{ transaction.status }}
              </q-chip>
            </q-item-section>
          </q-item>
        </q-list>

        <div v-else class="text-center q-pa-md text-grey-6">
          Nenhuma transação recente
        </div>

        <q-btn
          v-if="recentTransactions.length > 0"
          flat
          color="primary"
          label="Ver todas"
          class="full-width q-mt-md"
          @click="$router.push({ name: 'admin.transactions' })"
        />
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import axios from 'axios';

const $q = useQuasar();

const stats = ref(null);
const recentTransactions = ref([]);
const loading = ref(false);

async function loadDashboard() {
  loading.value = true;
  try {
    const response = await axios.get('/admin/dashboard');
    stats.value = response.data.stats;
    recentTransactions.value = response.data.recent_transactions;
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

function getTransactionIcon(type) {
  const icons = {
    transfer: 'swap_horiz',
    payment: 'payment',
    deposit: 'account_balance_wallet'
  };
  return icons[type] || 'account_balance';
}

function getTransactionDescription(transaction) {
  if (transaction.type === 'transfer') {
    return `Transferência: ${transaction.from_user?.name || 'N/A'} → ${transaction.to_user?.name || 'N/A'}`;
  }
  if (transaction.type === 'payment') {
    return `Pagamento: ${transaction.from_user?.name || 'N/A'} → ${transaction.to_user?.name || 'N/A'}`;
  }
  return `Depósito para ${transaction.to_user?.name || 'N/A'}`;
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

onMounted(() => {
  loadDashboard();
});
</script>
