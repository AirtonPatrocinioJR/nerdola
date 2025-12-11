<template>
  <q-page padding>
    <div class="text-h4 q-mb-md">Usuários</div>

    <!-- Filtros -->
    <q-card class="q-mb-md">
      <q-card-section>
        <div class="row q-gutter-md">
          <div class="col-12 col-md-5">
            <q-input
              v-model="filters.search"
              label="Buscar"
              outlined
              dense
              clearable
              @keyup.enter="loadUsers"
            >
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-md-3">
            <q-select
              v-model="filters.status"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              label="Status"
              clearable
              outlined
              dense
              @update:model-value="loadUsers"
            />
          </div>
          <div class="col-12 col-md-2">
            <q-btn
              color="primary"
              label="Buscar"
              class="full-width"
              @click="loadUsers"
            />
          </div>
          <div class="col-12 col-md-2">
            <q-btn
              color="positive"
              icon="download"
              label="Exportar"
              class="full-width"
              @click="exportUsers"
              :loading="exporting"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Lista de Usuários -->
    <q-card>
      <q-card-section>
        <q-table
          :rows="users"
          :columns="columns"
          :loading="loading"
          row-key="id"
          :pagination="pagination"
          @request="onRequest"
        >
          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <q-btn
                flat
                round
                dense
                icon="visibility"
                color="primary"
                @click="viewUser(props.row.id)"
              />
              <q-btn
                v-if="props.row.is_active"
                flat
                round
                dense
                icon="block"
                color="negative"
                @click="blockUser(props.row.id)"
              />
              <q-btn
                v-else
                flat
                round
                dense
                icon="check_circle"
                color="positive"
                @click="unblockUser(props.row.id)"
              />
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import axios from 'axios';

const router = useRouter();
const $q = useQuasar();

const users = ref([]);
const loading = ref(false);
const exporting = ref(false);
const pagination = ref({
  page: 1,
  rowsPerPage: 20,
  rowsNumber: 0
});

const filters = ref({
  search: null,
  status: null
});

const statusOptions = [
  { label: 'Ativo', value: 'active' },
  { label: 'Bloqueado', value: 'blocked' }
];

const columns = [
  { name: 'id', label: 'ID', field: 'id', align: 'left' },
  { name: 'name', label: 'Nome', field: 'name', align: 'left' },
  { name: 'email', label: 'E-mail', field: 'email', align: 'left' },
  { name: 'phone', label: 'Telefone', field: 'phone', align: 'left' },
  { name: 'balance', label: 'Saldo', field: row => row.wallet?.balance || 0, align: 'right', format: val => formatCurrency(val) },
  { name: 'status', label: 'Status', field: row => row.is_active ? 'Ativo' : 'Bloqueado', align: 'center' },
  { name: 'actions', label: 'Ações', align: 'center' }
];

async function onRequest(props) {
  loading.value = true;
  pagination.value = props.pagination;
  
  try {
    const params = {
      page: props.pagination.page,
      per_page: props.pagination.rowsPerPage || 20
    };
    
    // Adicionar filtro de busca apenas se tiver valor
    if (filters.value.search && filters.value.search.trim() !== '') {
      params.search = filters.value.search.trim();
    }
    
    // Adicionar filtro de status apenas se tiver valor
    if (filters.value.status) {
      params.status = filters.value.status;
    }

    const response = await axios.get('/admin/users', { params });
    users.value = response.data.data;
    pagination.value.rowsNumber = response.data.total;
    pagination.value.page = response.data.current_page;
  } catch (error) {
    console.error('Erro ao carregar usuários:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Erro ao carregar usuários'
    });
  } finally {
    loading.value = false;
  }
}

function loadUsers() {
  onRequest({ pagination: pagination.value });
}

function viewUser(id) {
  router.push({ name: 'admin.users.show', params: { id } });
}

async function blockUser(id) {
  $q.dialog({
    title: 'Confirmar',
    message: 'Deseja realmente bloquear este usuário?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await axios.post(`/admin/users/${id}/block`);
      $q.notify({
        type: 'positive',
        message: 'Usuário bloqueado com sucesso!'
      });
      loadUsers();
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Erro ao bloquear usuário'
      });
    }
  });
}

async function unblockUser(id) {
  $q.dialog({
    title: 'Confirmar',
    message: 'Deseja realmente desbloquear este usuário?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await axios.post(`/admin/users/${id}/unblock`);
      $q.notify({
        type: 'positive',
        message: 'Usuário desbloqueado com sucesso!'
      });
      loadUsers();
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

async function exportUsers() {
  exporting.value = true;
  try {
    const params = { ...filters.value };
    
    // Remover valores nulos
    Object.keys(params).forEach(key => {
      if (params[key] === null || params[key] === '') {
        delete params[key];
      }
    });

    // Criar URL com parâmetros
    const queryString = new URLSearchParams(params).toString();
    const url = `/admin/users/export${queryString ? '?' + queryString : ''}`;
    
    // Fazer download do arquivo
    const response = await axios.get(url, {
      responseType: 'blob',
      headers: {
        'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      }
    });

    // Criar link temporário para download
    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    });
    const link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    
    // Extrair nome do arquivo do header ou usar padrão
    const contentDisposition = response.headers['content-disposition'];
    let filename = 'usuarios.xlsx';
    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename="?(.+)"?/i);
      if (filenameMatch) {
        filename = filenameMatch[1];
      }
    }
    
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(link.href);

    $q.notify({
      type: 'positive',
      message: 'Planilha exportada com sucesso!'
    });
  } catch (error) {
    console.error('Erro ao exportar:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Erro ao exportar planilha'
    });
  } finally {
    exporting.value = false;
  }
}

onMounted(() => {
  loadUsers();
});
</script>
