<template>
  <div class="login-container">
    <div class="login-card">
      <!-- Dice Icon -->
      <div class="icon-container">
        <div class="dice-icon">🎲</div>
      </div>

      <!-- Title -->
      <h1 class="title">Board Game Cafe</h1>

      <!-- Subtitle -->
      <p class="subtitle">
        <span class="dice-emoji">🎲</span>
        Staff Login • POS System
      </p>

      <!-- Login Form -->
      <form @submit.prevent="handleLogin">
        <!-- Username Field -->
        <div class="form-group">
          <label class="form-label">Username</label>
          <input 
            type="email" 
            v-model="form.email" 
            placeholder="Enter your username" 
            required 
            class="form-input"
          />
        </div>

        <!-- Password Field -->
        <div class="form-group">
          <label class="form-label">Password</label>
          <input 
            type="password" 
            v-model="form.password" 
            placeholder="Enter your password" 
            required 
            class="form-input"
          />
        </div>

        <!-- Login Button -->
        <button 
          type="submit" 
          :disabled="loading"
          class="login-button"
        >
          <div v-if="loading" class="spinner"></div>
          <span v-else>Login to POS</span>
        </button>
      </form>

      <!-- Error Message -->
      <p v-if="errorMessage" class="error-message">
        {{ errorMessage }}
      </p>
    </div>
  </div>
</template>

<script>
import { reactive, ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

export default {
  setup() {
    const router = useRouter();
    
    // ตัวแปรรับค่าจากฟอร์ม
    const form = reactive({
      email: '',
      password: ''
    });
    
    const errorMessage = ref('');
    const loading = ref(false);

    const handleLogin = async () => {
        // เคลียร์ Error เก่าก่อนกด
        errorMessage.value = '';
        loading.value = true;

        try {
            const response = await axios.post('/api/login', {
                email: form.email,      // ✅ แก้ตรงนี้: ใช้ form.email
                password: form.password // ✅ แก้ตรงนี้: ใช้ form.password
            });

            // 1. เก็บ Token และ User Info
            localStorage.setItem('token', response.data.token);
            localStorage.setItem('user', JSON.stringify(response.data.user)); // บันทึก user object ทั้งหมด
            
            const role = response.data.user.role || 'staff';
            localStorage.setItem('role', role);
            
            // 2. เช็ค Role เพื่อเปลี่ยนหน้า
            if (role.toLowerCase() === 'admin') {
                router.push('/admin/dashboard'); // ไปหน้า Admin
            } else {
                router.push('/pos'); // ไปหน้าขายของ
            }

        } catch (error) {
            console.error(error);
            // แสดงข้อความ Error ที่หน้าจอ แทนการ Alert
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
/* Login Container - Full Screen with Cream Background */
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background-color: #fff8e7; /* Cream background from theme */
  padding: 20px;
}

/* Login Card - Centered White Card */
.login-card {
  background-color: #ffffff;
  border: 2px solid #deb887; /* Tan border */
  border-radius: 16px;
  padding: 40px 50px;
  max-width: 450px;
  width: 100%;
  box-shadow: 0 4px 12px rgba(139, 69, 19, 0.1);
  text-align: center;
}

/* Dice Icon Container */
.icon-container {
  display: flex;
  justify-content: center;
  margin-bottom: 24px;
}

.dice-icon {
  width: 120px;
  height: 120px;
  background: linear-gradient(135deg, #ff8c42, #ff7a29);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 60px;
  box-shadow: 0 4px 12px rgba(255, 140, 66, 0.3);
}

/* Title */
.title {
  font-size: 32px;
  font-weight: 700;
  color: #8b4513; /* Brown from theme */
  margin: 0 0 12px 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Subtitle */
.subtitle {
  font-size: 16px;
  color: #a0522d; /* Light brown */
  margin: 0 0 32px 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.dice-emoji {
  font-size: 14px;
}

/* Form Groups */
.form-group {
  margin-bottom: 20px;
  text-align: left;
}

.form-label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #654321; /* Dark brown */
  margin-bottom: 8px;
}

.form-input {
  width: 100%;
  padding: 12px 16px;
  font-size: 15px;
  border: 1px solid #deb887;
  border-radius: 8px;
  background-color: #ffffff;
  color: #654321;
  transition: all 0.3s ease;
  box-sizing: border-box;
}

.form-input::placeholder {
  color: #c19a6b;
}

.form-input:focus {
  outline: none;
  border-color: #ff8c42;
  box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
}

/* Login Button */
.login-button {
  width: 100%;
  padding: 14px 24px;
  font-size: 16px;
  font-weight: 600;
  color: #ffffff;
  background: linear-gradient(135deg, #ff8c42, #ff7a29);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.login-button:hover:not(:disabled) {
  background: linear-gradient(135deg, #ff7a29, #e67e22);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(255, 140, 66, 0.3);
}

.login-button:active:not(:disabled) {
  transform: translateY(0);
}

.login-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* Loading Spinner */
.spinner {
  width: 20px;
  height: 20px;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #fff;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Error Message */
.error-message {
  color: #c85a54; /* Terracotta from theme */
  font-size: 14px;
  font-weight: 600;
  margin-top: 20px;
  padding: 12px;
  background-color: #ffe4e1;
  border-radius: 8px;
  border-left: 4px solid #c85a54;
}

/* Responsive Design */
@media (max-width: 500px) {
  .login-card {
    padding: 30px 24px;
  }

  .title {
    font-size: 28px;
  }

  .dice-icon {
    width: 100px;
    height: 100px;
    font-size: 50px;
  }
}
</style>
