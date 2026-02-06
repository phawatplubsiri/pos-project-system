# Theme System Usage Guide

## 📚 วิธีใช้งาน Theme System

### 1. ใช้ CSS Variables (แนะนำ)

```vue
<template>
    <button class="my-button">คลิกที่นี่</button>
</template>

<style scoped>
.my-button {
    background-color: var(--color-primary);
    color: var(--color-text-white);
    padding: var(--spacing-md);
    border-radius: var(--radius-md);
    transition: var(--transition-normal);
}

.my-button:hover {
    background-color: var(--color-primary-hover);
}
</style>
```

### 2. ใช้ Utility Classes

```vue
<template>
    <div class="bg-secondary">
        <button class="btn-theme-primary">บันทึก</button>
        <button class="btn-theme-secondary">ยกเลิก</button>
        <span class="badge-theme-success">สำเร็จ</span>
    </div>
</template>
```

### 3. ใช้ useTheme Composable

```vue
<template>
    <div :style="{ backgroundColor: theme.colors.secondary }">
        <button :class="theme.getButtonClass('primary')" @click="handleClick">
            คลิก
        </button>

        <span :class="theme.getStatusBadgeClass(status)">
            {{ status }}
        </span>
    </div>
</template>

<script>
import { useTheme } from "@/composables/useTheme";

export default {
    setup() {
        const theme = useTheme();
        const status = "available";

        return { theme, status };
    },
};
</script>
```

## 🎨 Color Palette

### Primary (Orange)

- `--color-primary`: #FF8C42
- `--color-primary-dark`: #E67E22
- `--color-primary-light`: #FFB380
- `--color-primary-hover`: #FF7A29

### Secondary (Cream)

- `--color-secondary`: #FFF8E7
- `--color-secondary-dark`: #F5E6D3
- `--color-cream-accent`: #FFE4B5

### Accent (Brown)

- `--color-accent`: #8B4513
- `--color-accent-light`: #A0522D
- `--color-accent-dark`: #654321

### Status Colors

- `--color-success`: #D4A574 (Tan/Gold)
- `--color-warning`: #FF8C42 (Orange)
- `--color-danger`: #C85A54 (Terracotta)
- `--color-info`: #DEB887 (Burlywood)

## 🔧 Utility Classes

### Buttons

- `.btn-theme-primary` - Orange button
- `.btn-theme-secondary` - Cream button
- `.btn-theme-success` - Tan button
- `.btn-theme-danger` - Terracotta button

### Badges

- `.badge-theme-success` - Success badge
- `.badge-theme-danger` - Danger badge
- `.badge-theme-warning` - Warning badge
- `.badge-theme-info` - Info badge

### Backgrounds

- `.bg-primary`, `.bg-secondary`, `.bg-accent`
- `.bg-success`, `.bg-danger`, `.bg-warning`

### Text Colors

- `.text-primary`, `.text-secondary`, `.text-accent`
- `.text-white`, `.text-brown`

## 📦 useTheme Functions

### `getStatusColor(status)`

รับสีตามสถานะ

```js
const color = theme.getStatusColor("available"); // #D4A574
```

### `getStatusBadgeClass(status)`

รับ class ของ badge ตามสถานะ

```js
const badgeClass = theme.getStatusBadgeClass("busy"); // 'badge-theme-danger'
```

### `getButtonClass(variant)`

รับ class ของปุ่มตาม variant

```js
const btnClass = theme.getButtonClass("primary"); // 'btn-theme-primary'
```

### `applyThemeStyles(styleObj)`

แปลง style object ที่มี $ prefix เป็นสีจริง

```js
const styles = theme.applyThemeStyles({
    backgroundColor: "$primary",
    color: "$textWhite",
});
// { backgroundColor: '#FF8C42', color: '#FFFFFF' }
```

## ✅ ตัวอย่างการใช้งานจริง

### ปุ่มเปิดโต๊ะ

```vue
<button class="btn-theme-primary">
  เปิดโต๊ะ
</button>
```

### การ์ดโต๊ะ

```vue
<div
    class="card-theme"
    :style="{
        backgroundColor: theme.getStatusColor(table.status),
        color: 'white',
    }"
>
  {{ table.name }}
</div>
```

### Status Badge

```vue
<span :class="theme.getStatusBadgeClass(order.status)">
  {{ order.status }}
</span>
```
