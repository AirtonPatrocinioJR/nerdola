import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null);
  const token = ref(localStorage.getItem('token'));

  const isAuthenticated = computed(() => !!token.value && !!user.value);

  // Configurar axios com token se existir
  if (token.value) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
  }

  async function login(credentials) {
    try {
      const response = await axios.post('/login', credentials);
      token.value = response.data.token;
      user.value = response.data.user;
      localStorage.setItem('token', token.value);
      axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async function register(data) {
    try {
      const response = await axios.post('/register', data);
      token.value = response.data.token;
      user.value = response.data.user;
      localStorage.setItem('token', token.value);
      axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async function logout() {
    try {
      await axios.post('/logout');
    } catch (error) {
      console.error('Erro ao fazer logout:', error);
    } finally {
      token.value = null;
      user.value = null;
      localStorage.removeItem('token');
      delete axios.defaults.headers.common['Authorization'];
    }
  }

  async function fetchUser() {
    try {
      const response = await axios.get('/user');
      user.value = response.data;
      return response.data;
    } catch (error) {
      // Se for erro 401/403, limpar autenticação
      if (error.response?.status === 401 || error.response?.status === 403) {
        token.value = null;
        user.value = null;
        localStorage.removeItem('token');
        delete axios.defaults.headers.common['Authorization'];
      }
      throw error;
    }
  }

  // Carregar usuário se tiver token (apenas se não estiver já carregado)
  if (token.value && !user.value) {
    fetchUser().catch(() => {
      // Ignorar erro silenciosamente - será tratado quando necessário
    });
  }

  return {
    user,
    token,
    isAuthenticated,
    login,
    register,
    logout,
    fetchUser
  };
});

