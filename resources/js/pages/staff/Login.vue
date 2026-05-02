<template>
  <div class="flex justify-center items-center min-h-screen bg-[var(--color-bg-primary)] p-4">
    <div class="bg-[var(--color-bg-card)] border-2 border-[var(--color-border)] rounded-[var(--radius-lg)] p-10 max-w-[400px] w-full shadow-[var(--shadow-md)] text-center text-black">
      <!-- Icon Section -->
      <div class="flex justify-center mb-6">
        <div class="w-[100px] h-[100px] bg-gradient-to-br from-[var(--color-action)] to-[var(--color-action-hover)] rounded-full flex items-center justify-center text-[50px] shadow-[0_4px_12px_rgba(76,175,142,0.3)] text-white">
          <Dices :size="50" />
        </div>
      </div>

      <!-- Header Section -->
      <header>
        <h1 class="text-[28px] font-bold text-black m-0 mb-2">Board Game Cafe</h1>
        <p class="text-sm text-black mb-8 flex items-center justify-center gap-1.5">
          <Dices :size="16" class="brand-icon" />
          Staff Login • POS System
        </p>
      </header>

      <!-- Feedback Section -->
      <transition name="fade">
        <p v-if="errorMessage" class="text-[var(--color-danger)] bg-[var(--color-danger-light)] text-sm font-semibold my-6 p-3 rounded-[var(--radius-md)] border border-[var(--color-danger)]" role="alert">
          {{ errorMessage }}
        </p>
      </transition>

      <!-- PIN Login Form (Default) -->
      <div v-if="usePin" class="text-left">
        <div :class="['pin-container', { 'pin-error': pinError, 'shake': pinError }]">
          <div v-if="loading" class="flex gap-2 items-center justify-center">
            <div class="loading-dot"></div>
            <div class="loading-dot"></div>
            <div class="loading-dot"></div>
          </div>
          <div v-else class="flex justify-center items-center gap-2.5">
            <div 
              v-for="i in 6" 
              :key="i" 
              :class="['pin-dot', { 'filled': pinForm.pin.length >= i, 'showing-num': lastTypedIndex === i-1 }]"
            >
              <span v-if="lastTypedIndex === i-1">{{ pinForm.pin[i-1] }}</span>
            </div>
          </div>
        </div>
        <!-- PIN Error Message -->
        <transition name="fade">
          <p v-if="pinError" class="pin-error-message">
            {{ pinErrorMessage }}
          </p>
        </transition>

        <div class="grid grid-cols-3 gap-2.5 max-w-[240px] mx-auto">
          <button 
            v-for="num in [1,2,3,4,5,6,7,8,9]" 
            :key="num"
            @click="appendPin(num)"
            class="numpad-btn"
            :disabled="loading"
          >
            {{ num }}
          </button>
          <button @click="clearPin" class="numpad-btn text-[var(--color-danger)]" :disabled="loading">C</button>
          <button @click="appendPin(0)" class="numpad-btn" :disabled="loading">0</button>
          <button @click="backspacePin" class="numpad-btn" :disabled="loading || pinForm.pin.length === 0">
            ⌫
          </button>
        </div>

        <button type="button" @click="usePin = false" class="w-full bg-transparent border-none text-[var(--color-primary)] text-sm font-semibold cursor-pointer mt-6 underline">
          Login with Email & Password
        </button>
      </div>

      <!-- Email Login Form -->
      <form v-else @submit.prevent="handleLogin" class="text-left">
        <div class="mb-4">
          <label for="email" class="block text-sm font-semibold text-black mb-2">Email</label>
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

        <div class="mb-4">
          <label for="password" class="block text-sm font-semibold text-black mb-2">Password</label>
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
          class="w-full p-3.5 text-base font-semibold text-white bg-gradient-to-br from-[var(--color-action)] to-[var(--color-action-hover)] border-none rounded-[var(--radius-md)] cursor-pointer transition-all duration-300 mt-2 flex items-center justify-center hover:not-disabled:-translate-y-0.5 hover:not-disabled:shadow-[0_4px_12px_rgba(76,175,142,0.3)] disabled:opacity-70 disabled:cursor-not-allowed"
        >
          <div v-if="loading" class="spinner"></div>
          <span v-else>Login to POS</span>
        </button>

        <button type="button" @click="usePin = true" class="w-full bg-transparent border-none text-[var(--color-primary)] text-sm font-semibold cursor-pointer mt-6 underline">
          Back to PIN Login
        </button>
      </form>

    </div>
  </div>
</template>

<script>
import { reactive, ref, watch } from 'vue';
import axios from 'axios'; // Axios จะใช้ค่า defaults จาก bootstrap.js หากถูกนำเข้าอย่างถูกต้อง
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
    const pinError = ref(false);
    const pinErrorMessage = ref('');
    let maskTimer = null;
    let countdownInterval = null;

    const startCountdown = (seconds, type) => {
      if (countdownInterval) clearInterval(countdownInterval);
      
      let remaining = seconds;
      const updateMessage = () => {
        const msg = `กรุณาลองใหม่ภายใน ${remaining} วินาที`;
        if (type === 'pin') {
          pinError.value = true;
          pinErrorMessage.value = msg;
        } else {
          errorMessage.value = msg;
        }
      };

      updateMessage();

      countdownInterval = setInterval(() => {
        remaining--;
        if (remaining <= 0) {
          clearInterval(countdownInterval);
          if (type === 'pin') {
            pinError.value = false;
            pinErrorMessage.value = '';
            pinForm.pin = '';
          } else {
            errorMessage.value = '';
          }
        } else {
          updateMessage();
        }
      }, 1000);
    };

    const numpadNumbers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];

    const appendPin = (num) => {
      if (pinForm.pin.length < 6) {
        pinForm.pin += num;
        // ล้าง error เมื่อผู้ใช้พิมพ์ PIN ใหม่ (ถ้าไม่ได้ติด countdown)
        if (pinError.value && !countdownInterval) {
          pinError.value = false;
          pinErrorMessage.value = '';
        }
        
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
        if (error.response?.status === 429) {
          const retryAfter = error.response.data.retry_after || 60;
          startCountdown(retryAfter, 'email');
        } else {
          errorMessage.value = error.response?.data?.message || 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
        }
      } finally {
        loading.value = false;
      }
    };

    const handlePinLogin = async () => {
      if (pinForm.pin.length < 6) return;
      
      errorMessage.value = '';
      loading.value = true;

      try {
        await axios.get('/sanctum/csrf-cookie');
        const { data } = await axios.post('/api/login-pin', pinForm);
        processLoginSuccess(data);
      } catch (error) {
        console.error('PIN Login error:', error);
        if (error.response?.status === 429) {
          const retryAfter = error.response.data.retry_after || 60;
          startCountdown(retryAfter, 'pin');
        } else {
          pinError.value = true;
          pinErrorMessage.value = error.response?.data?.message || 'PIN ไม่ถูกต้อง';
          setTimeout(() => {
            pinForm.pin = '';
            pinError.value = false;
            pinErrorMessage.value = '';
          }, 1000);
        }
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
      errorMessage, loading, usePin, pinError, pinErrorMessage
    };
  }
};
</script>

<style scoped>
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
  transition: border-color 0.3s ease;
}

.pin-container.pin-error {
  border-color: var(--color-danger);
  background-color: rgba(239, 68, 68, 0.05);
}

.pin-container.shake {
  animation: shake 0.5s ease-in-out;
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

.loading-dot {
  width: 10px;
  height: 10px;
  background-color: var(--color-primary);
  border-radius: 50%;
  animation: pulse 0.8s infinite ease-in-out;
}

.loading-dot:nth-child(2) { animation-delay: 0.15s; }
.loading-dot:nth-child(3) { animation-delay: 0.3s; }

@keyframes pulse {
  0%, 100% { transform: scale(0.8); opacity: 0.5; }
  50% { transform: scale(1.2); opacity: 1; }
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

@keyframes shake {
  0% { transform: translateX(0); }
  10% { transform: translateX(-8px); }
  20% { transform: translateX(8px); }
  30% { transform: translateX(-8px); }
  40% { transform: translateX(8px); }
  50% { transform: translateX(-8px); }
  60% { transform: translateX(8px); }
  70% { transform: translateX(-4px); }
  80% { transform: translateX(4px); }
  90% { transform: translateX(-2px); }
  100% { transform: translateX(0); }
}

.pin-error-message {
  color: var(--color-danger);
  font-size: 13px;
  font-weight: 600;
  margin: -20px 0 16px 0;
  text-align: center;
  animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-8px); }
  to { opacity: 1; transform: translateY(0); }
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

@media (max-width: 480px) {
  /* On very small screens, make card borderless */
  .flex.justify-center > div:first-child {
    padding: 30px 20px;
    border: none;
    box-shadow: none;
    background: transparent;
  }
}
</style>