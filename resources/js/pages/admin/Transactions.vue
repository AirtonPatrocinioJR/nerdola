<template>
  <q-page padding>
    <div class="text-h4 q-mb-md">Transações</div>

    <!-- Filtros -->
    <q-card class="q-mb-md">
      <q-card-section>
        <div class="row q-gutter-md">
          <div class="col-12 col-md-3">
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
          <div class="col-12 col-md-3">
            <q-select
              v-model="filters.status"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              label="Status"
              clearable
              outlined
              dense
              emit-value
              map-options
            />
          </div>
          <div class="col-12 col-md-3">
            <q-select
              v-model="filters.user_id"
              :options="users"
              option-label="label"
              option-value="id"
              label="Usuário"
              clearable
              outlined
              dense
              emit-value
              map-options
              :loading="loadingUsers"
              use-input
              input-debounce="300"
              @filter="filterUsers"
              hint="Digite para buscar"
            >
              <template v-slot:no-option>
                <q-item>
                  <q-item-section class="text-grey">
                    Nenhum usuário encontrado
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
          </div>
          <div class="col-12 col-md-3">
            <div class="row q-gutter-sm">
              <div class="col">
                <q-btn
                  color="primary"
                  label="Filtrar"
                  class="full-width"
                  @click="loadTransactions"
                />
              </div>
              <div class="col-auto">
                <q-btn
                  flat
                  icon="clear"
                  @click="clearFilters"
                />
              </div>
            </div>
          </div>
        </div>
        <div class="row q-gutter-md q-mt-md">
          <div class="col-12 col-md-6">
            <q-input
              v-model="filters.date_from"
              label="Data Inicial"
              type="date"
              outlined
              dense
            />
          </div>
          <div class="col-12 col-md-6">
            <q-input
              v-model="filters.date_to"
              label="Data Final"
              type="date"
              outlined
              dense
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Tabela de Transações -->
    <q-card>
      <q-card-section>
        <q-table
          :rows="transactions"
          :columns="columns"
          :loading="loading"
          row-key="id"
          :pagination="pagination"
          @request="onRequest"
          :rows-per-page-options="[10, 25, 50, 100]"
        >
          <template v-slot:no-data>
            <div class="full-width row flex-center text-grey-6 q-gutter-sm">
              <span>Nenhuma transação encontrada</span>
            </div>
          </template>
        </q-table>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import axios from 'axios';

const $q = useQuasar();

const transactions = ref([]);
const users = ref([]);
const allUsers = ref([]); // Lista completa de usuários para filtro
const loading = ref(false);
const loadingUsers = ref(false);
const pagination = ref({
  page: 1,
  rowsPerPage: 50,
  rowsNumber: 0
});

const filters = ref({
  type: null,
  status: null,
  user_id: null,
  date_from: null,
  date_to: null
});

const typeOptions = [
  { label: 'Transferência', value: 'transfer' },
  { label: 'Pagamento', value: 'payment' },
  { label: 'Depósito', value: 'deposit' }
];

const statusOptions = [
  { label: 'Completa', value: 'completed' },
  { label: 'Pendente', value: 'pending' },
  { label: 'Cancelada', value: 'cancelled' },
  { label: 'Falhou', value: 'failed' }
];

const columns = [
  { name: 'id', label: 'ID', field: 'id', align: 'left', sortable: true },
  { 
    name: 'type', 
    label: 'Tipo', 
    field: 'type', 
    align: 'left',
    format: val => {
      const types = {
        transfer: 'Transferência',
        payment: 'Pagamento',
        deposit: 'Depósito'
      };
      return types[val] || val;
    },
    sortable: true
  },
  { 
    name: 'from', 
    label: 'De', 
    field: row => row.from_user?.name || 'N/A', 
    align: 'left' 
  },
  { 
    name: 'to', 
    label: 'Para', 
    field: row => row.to_user?.name || 'N/A', 
    align: 'left' 
  },
  { 
    name: 'amount', 
    label: 'Valor', 
    field: 'amount', 
    align: 'right', 
    format: val => formatCurrency(val),
    sortable: true
  },
  { 
    name: 'status', 
    label: 'Status', 
    field: 'status', 
    align: 'center',
    format: val => {
      const statuses = {
        completed: 'Completa',
        pending: 'Pendente',
        cancelled: 'Cancelada',
        failed: 'Falhou'
      };
      return statuses[val] || val;
    },
    sortable: true
  },
  { 
    name: 'date', 
    label: 'Data', 
    field: 'created_at', 
    align: 'left', 
    format: val => formatDate(val),
    sortable: true
  }
];

async function loadUsers() {
  loadingUsers.value = true;
  try {
    // Tentar carregar da lista simples primeiro
    try {
      const usersResponse = await axios.get('/admin/users/list');
      if (usersResponse.data && Array.isArray(usersResponse.data)) {
        allUsers.value = usersResponse.data.map(user => ({
          id: user.id,
          name: user.name,
          email: user.email,
          label: `${user.name} (${user.email})`
        }));
        users.value = [...allUsers.value];
        return;
      }
    } catch (listError) {
      console.log('Endpoint /list não disponível, tentando endpoint paginado');
    }
    
    // Fallback: carregar do endpoint paginado
    const usersResponse = await axios.get('/admin/users', { params: { per_page: 1000 } });
    let usersList = [];
    
    if (usersResponse.data && usersResponse.data.data) {
      usersList = usersResponse.data.data;
    } else if (Array.isArray(usersResponse.data)) {
      usersList = usersResponse.data;
    }
    
    // Mapear usuários para o formato esperado
    allUsers.value = usersList.map(user => ({
      id: user.id,
      name: user.name,
      email: user.email,
      label: `${user.name} (${user.email})`
    }));
    
    users.value = [...allUsers.value];
  } catch (error) {
    console.error('Erro ao carregar usuários:', error);
    // Tentar carregar da resposta de transações como último recurso
    try {
      const response = await axios.get('/admin/transactions', { params: { page: 1, load_users: true } });
      if (response.data && response.data.users && Array.isArray(response.data.users)) {
        allUsers.value = response.data.users.map(user => ({
          id: user.id,
          name: user.name,
          email: user.email,
          label: `${user.name} (${user.email})`
        }));
        users.value = [...allUsers.value];
      }
    } catch (err) {
      console.error('Erro ao carregar usuários do endpoint alternativo:', err);
      $q.notify({
        type: 'warning',
        message: 'Não foi possível carregar a lista de usuários'
      });
    }
  } finally {
    loadingUsers.value = false;
  }
}

function filterUsers(val, update) {
  if (val === '') {
    update(() => {
      users.value = [...allUsers.value];
    });
    return;
  }

  update(() => {
    const needle = val.toLowerCase();
    users.value = allUsers.value.filter(user => 
      user.name.toLowerCase().indexOf(needle) > -1 ||
      user.email.toLowerCase().indexOf(needle) > -1
    );
  });
}

async function onRequest(props) {
  loading.value = true;
  
  try {
    const params = {
      page: props.pagination.page,
      ...filters.value
    };
    
    // Remover valores nulos, undefined e strings vazias
    Object.keys(params).forEach(key => {
      if (params[key] === null || params[key] === '' || params[key] === undefined) {
        delete params[key];
      }
    });

    const response = await axios.get('/admin/transactions', { params });
    
    // Atualizar paginação
    if (props.pagination) {
      pagination.value = {
        ...props.pagination,
        rowsNumber: 0
      };
    }
    
    // Processar resposta
    if (response.data) {
      if (response.data.transactions) {
        // Estrutura com transactions e users separados
        transactions.value = response.data.transactions.data || [];
        pagination.value.rowsNumber = response.data.transactions.total || 0;
        pagination.value.page = response.data.transactions.current_page || 1;
        pagination.value.rowsPerPage = response.data.transactions.per_page || 50;
        
        // Carregar usuários se ainda não foram carregados
        if (response.data.users && users.value.length === 0) {
          users.value = response.data.users;
        }
      } else if (response.data.data) {
        // Estrutura direta de paginação
        transactions.value = response.data.data || [];
        pagination.value.rowsNumber = response.data.total || 0;
        pagination.value.page = response.data.current_page || 1;
        pagination.value.rowsPerPage = response.data.per_page || 50;
      } else if (Array.isArray(response.data)) {
        // Array direto
        transactions.value = response.data;
        pagination.value.rowsNumber = response.data.length;
      } else {
        transactions.value = [];
        pagination.value.rowsNumber = 0;
      }
    } else {
      transactions.value = [];
      pagination.value.rowsNumber = 0;
    }
  } catch (error) {
    console.error('Erro ao carregar transações:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Erro ao carregar transações'
    });
    transactions.value = [];
    pagination.value.rowsNumber = 0;
  } finally {
    loading.value = false;
  }
}

function loadTransactions() {
  // Resetar para primeira página ao filtrar
  pagination.value.page = 1;
  onRequest({ pagination: pagination.value });
}

function clearFilters() {
  filters.value = {
    type: null,
    status: null,
    user_id: null,
    date_from: null,
    date_to: null
  };
  pagination.value.page = 1;
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

onMounted(async () => {
  // Carregar usuários primeiro
  await loadUsers();
  // Depois carregar transações
  loadTransactions();
});
</script>
