<template>
  <div class="example-container">
    <h2 class="text-brown">🎨 Theme System Examples</h2>
    
    <!-- Method 1: CSS Variables -->
    <section class="section">
      <h3>1. Using CSS Variables</h3>
      <button class="custom-btn">Custom Button with CSS Vars</button>
    </section>

    <!-- Method 2: Utility Classes -->
    <section class="section bg-secondary">
      <h3>2. Using Utility Classes</h3>
      <div class="button-group">
        <button class="btn-theme-primary">Primary</button>
        <button class="btn-theme-secondary">Secondary</button>
        <button class="btn-theme-success">Success</button>
        <button class="btn-theme-danger">Danger</button>
      </div>
      
      <div class="badge-group">
        <span class="badge-theme-success">Available</span>
        <span class="badge-theme-warning">Pending</span>
        <span class="badge-theme-danger">Busy</span>
        <span class="badge-theme-info">Info</span>
      </div>
    </section>

    <!-- Method 3: useTheme Composable -->
    <section class="section">
      <h3>3. Using useTheme Composable</h3>
      
      <div class="status-cards">
        <div 
          v-for="status in statuses" 
          :key="status"
          class="status-card"
          :style="{ 
            backgroundColor: theme.getStatusColor(status),
            color: 'white'
          }"
        >
          <div class="status-name">{{ status }}</div>
          <span :class="theme.getStatusBadgeClass(status)">
            {{ status }}
          </span>
        </div>
      </div>

      <div class="color-palette">
        <h4>Color Palette:</h4>
        <div class="color-grid">
          <div 
            v-for="(color, name) in theme.colors" 
            :key="name"
            class="color-item"
          >
            <div 
              class="color-box" 
              :style="{ backgroundColor: color }"
            ></div>
            <div class="color-name">{{ name }}</div>
            <div class="color-value">{{ color }}</div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { useTheme } from '@/composables/useTheme';

export default {
  name: 'ThemeExample',
  setup() {
    const theme = useTheme();
    
    const statuses = ['available', 'busy', 'pending', 'completed', 'cancelled'];

    return {
      theme,
      statuses
    };
  }
};
</script>

<style scoped>
/* Method 1: Using CSS Variables */
.custom-btn {
  background-color: var(--color-primary);
  color: var(--color-text-white);
  padding: var(--spacing-md) var(--spacing-lg);
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  font-weight: 600;
  transition: var(--transition-normal);
  box-shadow: var(--shadow-sm);
}

.custom-btn:hover {
  background-color: var(--color-primary-hover);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

/* Container */
.example-container {
  padding: var(--spacing-xl);
  background-color: var(--color-bg-primary);
  min-height: 100vh;
  font-family: 'Sarabun', sans-serif;
}

.section {
  background-color: var(--color-bg-card);
  padding: var(--spacing-lg);
  margin-bottom: var(--spacing-lg);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
}

.section h3 {
  color: var(--color-text-primary);
  margin-bottom: var(--spacing-md);
}

/* Button Group */
.button-group {
  display: flex;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-lg);
  flex-wrap: wrap;
}

/* Badge Group */
.badge-group {
  display: flex;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}

/* Status Cards */
.status-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-lg);
}

.status-card {
  padding: var(--spacing-lg);
  border-radius: var(--radius-md);
  text-align: center;
  box-shadow: var(--shadow-sm);
  transition: var(--transition-normal);
}

.status-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
}

.status-name {
  font-size: 1.2rem;
  font-weight: bold;
  margin-bottom: var(--spacing-sm);
  text-transform: capitalize;
}

/* Color Palette */
.color-palette {
  margin-top: var(--spacing-lg);
}

.color-palette h4 {
  color: var(--color-text-primary);
  margin-bottom: var(--spacing-md);
}

.color-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: var(--spacing-md);
}

.color-item {
  text-align: center;
}

.color-box {
  width: 100%;
  height: 60px;
  border-radius: var(--radius-sm);
  margin-bottom: var(--spacing-xs);
  border: 1px solid var(--color-border-light);
}

.color-name {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  margin-bottom: 2px;
}

.color-value {
  font-size: 0.7rem;
  color: var(--color-text-light);
  font-family: monospace;
}
</style>
