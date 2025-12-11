<template>
  <q-page padding>
    <div class="row items-center q-mb-md">
      <q-btn flat icon="arrow_back" @click="$router.back()" />
      <div class="text-h4 q-ml-sm">Detalhes do Usuário</div>
    </div>

    <div v-if="user" class="row q-gutter-md">
      <!-- Informações do Usuário -->
      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <div class="text-h6 q-mb-md">Informações</div>
            <q-list>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Nome</q-item-label>
                  <q-item-label>{{ user.name }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>E-mail</q-item-label>
                  <q-item-label>{{ user.email }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Telefone</q-item-label>
                  <q-item-label>{{ user.phone }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Status</q-item-label>
                  <q-item-label>
                    <q-chip
                      :color="user.is_active ? 'positive' : 'negative'"
                      text-color="white"
                    >
                      {{ user.is_active ? 'Ativo' : 'Bloqueado' }}
                    </q-chip>
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>

        <!-- Carteira -->
        <q-card class="q-mt-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">Carteira</div>
            <q-list>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Código</q-item-label>
                  <q-item-label>{{ user.wallet?.code }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Saldo</q-item-label>
                  <q-item-label class="text-h6 text-primary">
                    {{ formatCurrency(user.wallet?.balance || 0) }}
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>

        <!-- Ações -->
        <q-card class="q-mt-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">Ações</div>
            <div class="q-gutter-sm">
              <q-btn
                v-if="user.is_active"
                color="negative"
                label="Bloquear Usuário"
                icon="block"
                @click="blockUser"
              />
              <q-btn
                v-else
                color="positive"
                label="Desbloquear Usuário"
                icon="check_circle"
                @click="unblockUser"
              />
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Transações -->
      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <div class="text-h6 q-mb-md">Últimas Transações</div>
            <q-list v-if="transactions.length > 0" separator>
              <q-item
                v-for="transaction in transactions"
                :key="transaction.id"
              >
                <q-item-section>
                  <q-item-label>{{ getTransactionDescription(transaction) }}</q-item-label>
                  <q-item-label caption>{{ formatDate(transaction.created_at) }}</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-item-label class="text-weight-bold">
                    {{ formatCurrency(transaction.amount) }}
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
            <div v-else class="text-center q-pa-md text-grey-6">
              Nenhuma transação encontrada
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-inner-loading :showing="loading" />
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();

const user = ref(null);
const transactions = ref([]);
const loading = ref(false);

async function loadUser() {
  loading.value = true;
  try {
    const response = await axios.get(`/admin/users/${route.params.id}`);
    user.value = response.data.user;
    transactions.value = response.data.transactions;
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar usuário'
    });
    router.back();
  } finally {
    loading.value = false;
  }
}

async function blockUser() {
  $q.dialog({
    title: 'Confirmar',
    message: 'Deseja realmente bloquear este usuário?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await axios.post(`/admin/users/${user.value.id}/block`);
      $q.notify({
        type: 'positive',
        message: 'Usuário bloqueado com sucesso!'
      });
      loadUser();
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Erro ao bloquear usuário'
      });
    }
  });
}

async function unblockUser() {
  $q.dialog({
    title: 'Confirmar',
    message: 'Deseja realmente desbloquear este usuário?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await axios.post(`/admin/users/${user.value.id}/unblock`);
      $q.notify({
        type: 'positive',
        message: 'Usuário desbloqueado com sucesso!'
      });
      loadUser();
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Erro ao desbloquear usuário'
      });
    }
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

function getTransactionDescription(transaction) {
  if (transaction.type === 'transfer') {
    return `Transferência: ${transaction.from_user?.name || 'N/A'} → ${transaction.to_user?.name || 'N/A'}`;
  }
  if (transaction.type === 'payment') {
    return `Pagamento: ${transaction.from_user?.name || 'N/A'} → ${transaction.to_user?.name || 'N/A'}`;
  }
  return `Depósito`;
}

onMounted(() => {
  loadUser();
});
</script>
