<template>
  <div class="login-container">
    <div class="login-card">
      <!-- Icon Section -->
      <div class="icon-section">
        <div class="dice-badge">
          <Dices :size="50" />
        </div>
      </div>

      <!-- Header Section -->
      <header class="login-header">
        <h1 class="brand-title">Board Game Cafe</h1>
        <p class="brand-subtitle">
          <Dices :size="16" class="brand-icon" />
          Staff Login • POS System
        </p>
      </header>

      <!-- PIN Login Form (Default) -->
      <div v-if="usePin" class="login-form">
        <div class="pin-container">
          <div v-if="loading" class="loading-overlay">
            <div class="loading-dot"></div>
            <div class="loading-dot"></div>
            <div class="loading-dot"></div>
          </div>
          <div v-else class="pin-display">
            <div 
              v-for="i in 6" 
              :key="i" 
              :class="['pin-dot', { 'filled': pinForm.pin.length >= i, 'showing-num': lastTypedIndex === i-1 }]"
            >
              <span v-if="lastTypedIndex === i-1">{{ pinForm.pin[i-1] }}</span>
            </div>
          </div>
        </div>

        <div class="numpad">
          <button 
            v-for="num in [1,2,3,4,5,6,7,8,9]" 
            :key="num"
            @click="appendPin(num)"
            class="numpad-btn"
            :disabled="loading"
          >
            {{ num }}
          </button>
          <button @click="clearPin" class="numpad-btn clear-btn" :disabled="loading">C</button>
          <button @click="appendPin(0)" class="numpad-btn" :disabled="loading">0</button>
          <button @click="backspacePin" class="numpad-btn backspace-btn" :disabled="loading || pinForm.pin.length === 0">
            ⌫
          </button>
        </div>

        <button type="button" @click="usePin = false" class="btn-toggle-mode">
          Login with Email & Password
        </button>
      </div>

      <!-- Email Login Form -->
      <form v-else @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label for="email" class="form-label">Email</label>
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

        <button type="button" @click="usePin = true" class="btn-toggle-mode">
          Back to PIN Login
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
import { reactive, ref, watch } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { Dices } from 'lucide-vue-next';

export default {
  name: 'Login',
  components: {
    Dices
  },
  setup() {
    const router = useRouter();
    const errorMessage = ref('');
    const loading = ref(false);
    const usePin = ref(true); // Default เป็น PIN

    const form = reactive({
      email: '',
      password: ''
    });

    const pinForm = reactive({
      pin: ''
    });

    const lastTypedIndex = ref(-1);
    let maskTimer = null;

    const numpadNumbers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];

    const appendPin = (num) => {
      if (pinForm.pin.length < 6) {
        pinForm.pin += num;
        
        // Show the number briefly
        lastTypedIndex.value = pinForm.pin.length - 1;
        if (maskTimer) clearTimeout(maskTimer);
        maskTimer = setTimeout(() => {
          lastTypedIndex.value = -1;
        }, 700);
      }
    };

    const clearPin = () => {
      pinForm.pin = '';
      lastTypedIndex.value = -1;
    };

    const backspacePin = () => {
      pinForm.pin = pinForm.pin.slice(0, -1);
      lastTypedIndex.value = -1;
    };

    // Auto-login when PIN reaches 6 digits
    watch(() => pinForm.pin, (newPin) => {
      if (newPin.length === 6) {
        handlePinLogin();
      }
    });

    const handleLogin = async () => {
      errorMessage.value = '';
      loading.value = true;

      try {
        const { data } = await axios.post('/api/login', form);
        processLoginSuccess(data);
      } catch (error) {
        console.error('Login error:', error);
        errorMessage.value = error.response?.data?.message || 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
      } finally {
        loading.value = false;
      }
    };

    const handlePinLogin = async () => {
      if (pinForm.pin.length < 6) return;
      
      errorMessage.value = '';
      loading.value = true;

      try {
        const { data } = await axios.post('/api/login-pin', pinForm);
        processLoginSuccess(data);
      } catch (error) {
        console.error('PIN Login error:', error);
        errorMessage.value = error.response?.data?.message || 'PIN ไม่ถูกต้อง';
        clearPin(); // ล้าง PIN เมื่อกรอกผิด
      } finally {
        loading.value = false;
      }
    };

    const processLoginSuccess = (data) => {
      localStorage.setItem('token', data.token);
      localStorage.setItem('user', JSON.stringify(data.user));
      
      const role = data.user.role?.toLowerCase() || 'staff';
      localStorage.setItem('role', role);
      
      const redirectPath = role === 'admin' ? '/admin/dashboard' : '/pos';
      router.push(redirectPath);
    };

    return { 
      form, pinForm, numpadNumbers, lastTypedIndex,
      handleLogin, handlePinLogin, appendPin, clearPin, backspacePin,
      errorMessage, loading, usePin 
    };
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
  color: #000000;
}

.icon-section {
  display: flex;
  justify-content: center;
  margin-bottom: var(--spacing-lg);
}

.dice-badge {
  width: 100px;
  height: 100px;
  background: linear-gradient(135deg, var(--color-action), var(--color-action-hover));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 50px;
  box-shadow: 0 4px 12px rgba(76, 175, 142, 0.3);
  color: white;
}

.brand-title {
  font-size: 28px;
  font-weight: 700;
  color: #000000;
  margin: 0 0 8px 0;
}

.brand-subtitle {
  font-size: 14px;
  color: #000000;
  margin: 0 0 32px 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.login-form {
  text-align: left;
}

.pin-container {
  background-color: var(--color-bg-primary);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: 10px;
  margin: 0 auto 24px auto;
  max-width: 180px;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 44px;
}

.pin-display {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
}

.pin-dot {
  width: 12px;
  height: 12px;
  border: 2px solid var(--color-primary);
  border-radius: 50%;
  transition: all 0.2s ease;
  background-color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  color: #000000;
}

.pin-dot.filled {
  background-color: var(--color-primary);
  border-color: var(--color-primary);
  transform: scale(1.1);
}

.pin-dot.showing-num {
  background-color: white !important;
  border-color: var(--color-primary);
  border-radius: 4px;
  width: 20px;
  height: 24px;
  transform: scale(1.2);
}

.loading-overlay {
  display: flex;
  gap: 8px;
  align-items: center;
  justify-content: center;
}

.loading-dot {
  width: 10px;
  height: 10px;
  background-color: var(--color-primary);
  border-radius: 50%;
  animation: pulse 0.8s infinite ease-in-out;
}

.loading-dot:nth-child(2) {
  animation-delay: 0.15s;
}

.loading-dot:nth-child(3) {
  animation-delay: 0.3s;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(0.8);
    opacity: 0.5;
  }
  50% {
    transform: scale(1.2);
    opacity: 1;
  }
}

.numpad {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  max-width: 240px;
  margin: 0 auto;
}

.numpad-btn {
  height: 50px;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background-color: white;
  font-size: 18px;
  font-weight: 600;
  color: #000000;
  cursor: pointer;
  transition: all 0.2s;
}

.numpad-btn:hover:not(:disabled) {
  background-color: var(--color-bg-primary);
  border-color: var(--color-primary);
}

.numpad-btn:active:not(:disabled) {
  transform: scale(0.95);
  background-color: var(--color-primary);
  color: white;
}

.clear-btn {
  color: var(--color-danger);
}

.backspace-btn {
  color: #000000;
}

.form-group {
  margin-bottom: var(--spacing-md);
}

.form-label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #000000;
  margin-bottom: 8px;
}

.form-input {
  width: 100%;
  padding: 12px 16px;
  font-size: 15px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  color: #000000;
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
  background: linear-gradient(135deg, var(--color-action), var(--color-action-hover));
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
  box-shadow: 0 4px 12px rgba(76, 175, 142, 0.3);
}

.btn-login:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-toggle-mode {
  width: 100%;
  background: none;
  border: none;
  color: var(--color-primary);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 24px;
  text-decoration: underline;
}

.spinner-small {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 0.8s linear infinite;
  margin: 0 auto;
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