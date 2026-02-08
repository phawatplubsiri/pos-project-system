<template>
  <div class="login-container">
    <div class="login-card">
      <!-- Icon Section -->
      <div class="icon-section">
        <div class="dice-badge">🎲</div>
      </div>

      <!-- Header Section -->
      <header class="login-header">
        <h1 class="brand-title">Board Game Cafe</h1>
        <p class="brand-subtitle">
          <span class="emoji">🎲</span>
          Staff Login • POS System
        </p>
      </header>

      <!-- Login Form -->
      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label for="email" class="form-label">Username</label>
          <input 
            id="email"
            type="email" 
            v-model="form.email" 
            placeholder="Enter your email" 
            required 
            class="form-input"
            :disabled="loading"
          />
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Password</label>
          <input 
            id="password"
            type="password" 
            v-model="form.password" 
            placeholder="Enter your password" 
            required 
            class="form-input"
            :disabled="loading"
          />
        </div>

        <button 
          type="submit" 
          :disabled="loading"
          class="btn-login"
        >
          <div v-if="loading" class="spinner"></div>
          <span v-else>Login to POS</span>
        </button>
      </form>

      <!-- Feedback Section -->
      <transition name="fade">
        <p v-if="errorMessage" class="error-alert" role="alert">
          {{ errorMessage }}
        </p>
      </transition>
    </div>
  </div>
</template>

<script>
import { reactive, ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

export default {
  name: 'Login',
  setup() {
    const router = useRouter();
    const errorMessage = ref('');
    const loading = ref(false);

    const form = reactive({
      email: '',
      password: ''
    });

    const handleLogin = async () => {
      errorMessage.value = '';
      loading.value = true;

      try {
        const { data } = await axios.post('/api/login', form);

        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));
        
        const role = data.user.role?.toLowerCase() || 'staff';
        localStorage.setItem('role', role);
        
        const redirectPath = role === 'admin' ? '/admin/dashboard' : '/pos';
        router.push(redirectPath);

      } catch (error) {
        console.error('Login error:', error);
        errorMessage.value = error.response?.data?.message || 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
      } finally {
        loading.value = false;
      }
    };

    return { form, handleLogin, errorMessage, loading };
  }
};
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background-color: var(--color-bg-primary);
  padding: var(--spacing-md);
}

.login-card {
  background-color: var(--color-bg-card);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: 40px;
  max-width: 400px;
  width: 100%;
  box-shadow: var(--shadow-md);
  text-align: center;
}

.icon-section {
  display: flex;
  justify-content: center;
  margin-bottom: var(--spacing-lg);
}

.dice-badge {
  width: 100px;
  height: 100px;
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 50px;
  box-shadow: 0 4px 12px rgba(255, 140, 66, 0.3);
  color: white;
}

.brand-title {
  font-size: 28px;
  font-weight: 700;
  color: var(--color-accent);
  margin: 0 0 8px 0;
}

.brand-subtitle {
  font-size: 14px;
  color: var(--color-text-light);
  margin: 0 0 32px 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.login-form {
  text-align: left;
}

.form-group {
  margin-bottom: var(--spacing-md);
}

.form-label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text-primary);
  margin-bottom: 8px;
}

.form-input {
  width: 100%;
  padding: 12px 16px;
  font-size: 15px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  color: var(--color-text-primary);
  transition: var(--transition-normal);
  background-color: white;
}

.form-input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
}

.form-input:disabled {
  background-color: var(--color-bg-secondary);
  cursor: not-allowed;
}

.btn-login {
  width: 100%;
  padding: 14px;
  font-size: 16px;
  font-weight: 600;
  color: white;
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover));
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: var(--transition-normal);
  margin-top: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-login:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(255, 140, 66, 0.3);
}

.btn-login:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-alert {
  color: white;
  background-color: var(--color-danger);
  font-size: 14px;
  font-weight: 600;
  margin-top: 24px;
  padding: 12px;
  border-radius: var(--radius-md);
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

@media (max-width: 480px) {
  .login-card {
    padding: 30px 20px;
    border: none;
    box-shadow: none;
    background: transparent;
  }
}
</style>