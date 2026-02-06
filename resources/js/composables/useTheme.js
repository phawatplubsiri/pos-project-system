/**
 * useTheme Composable
 * 
 * Provides access to theme colors and utility functions
 * for consistent theming across Vue components
 */

export function useTheme() {
  // Color palette object
  const colors = {
    // Primary - Orange
    primary: '#FF8C42',
    primaryDark: '#E67E22',
    primaryLight: '#FFB380',
    primaryHover: '#FF7A29',

    // Secondary - Cream
    secondary: '#FFF8E7',
    secondaryDark: '#F5E6D3',
    secondaryLight: '#FFFEF9',
    creamAccent: '#FFE4B5',

    // Accent - Brown
    accent: '#8B4513',
    accentLight: '#A0522D',
    accentDark: '#654321',

    // Status Colors
    success: '#D4A574',      // Tan/Gold
    successDark: '#C19A6B',
    warning: '#FF8C42',      // Orange
    danger: '#C85A54',       // Terracotta
    dangerDark: '#B44A3E',
    info: '#DEB887',         // Burlywood

    // Text
    textPrimary: '#654321',
    textSecondary: '#8B4513',
    textLight: '#A0522D',
    textWhite: '#FFFFFF',

    // Backgrounds
    bgPrimary: '#FFF8E7',
    bgSecondary: '#F5E6D3',
    bgCard: '#FFFFFF',
    bgHover: '#FFE4B5',

    // Borders
    border: '#DEB887',
    borderLight: '#F5E6D3',
    borderDark: '#A0522D',
  };

  // Button style classes
  const buttonClasses = {
    primary: 'btn-theme-primary',
    secondary: 'btn-theme-secondary',
    success: 'btn-theme-success',
    danger: 'btn-theme-danger',
  };

  // Badge style classes
  const badgeClasses = {
    success: 'badge-theme-success',
    danger: 'badge-theme-danger',
    warning: 'badge-theme-warning',
    info: 'badge-theme-info',
  };

  /**
   * Get status color based on status string
   * @param {string} status - Status name (available, busy, pending, etc.)
   * @returns {string} Color hex code
   */
  const getStatusColor = (status) => {
    const statusMap = {
      available: colors.success,
      busy: colors.danger,
      pending: colors.warning,
      completed: colors.success,
      cancelled: colors.danger,
      confirming: colors.warning,
    };
    return statusMap[status?.toLowerCase()] || colors.info;
  };

  /**
   * Get badge class based on status
   * @param {string} status - Status name
   * @returns {string} Badge class name
   */
  const getStatusBadgeClass = (status) => {
    const statusMap = {
      available: badgeClasses.success,
      busy: badgeClasses.danger,
      pending: badgeClasses.warning,
      completed: badgeClasses.success,
      cancelled: badgeClasses.danger,
      confirming: badgeClasses.warning,
    };
    return statusMap[status?.toLowerCase()] || badgeClasses.info;
  };

  /**
   * Get button class based on variant
   * @param {string} variant - Button variant (primary, secondary, success, danger)
   * @returns {string} Button class name
   */
  const getButtonClass = (variant = 'primary') => {
    return buttonClasses[variant] || buttonClasses.primary;
  };

  /**
   * Apply inline styles with theme colors
   * @param {object} styleObj - Style object with theme color keys
   * @returns {object} Style object with actual color values
   */
  const applyThemeStyles = (styleObj) => {
    const result = {};
    for (const [key, value] of Object.entries(styleObj)) {
      // If value starts with '$', treat it as a color variable
      if (typeof value === 'string' && value.startsWith('$')) {
        const colorKey = value.slice(1); // Remove '$'
        result[key] = colors[colorKey] || value;
      } else {
        result[key] = value;
      }
    }
    return result;
  };

  return {
    colors,
    buttonClasses,
    badgeClasses,
    getStatusColor,
    getStatusBadgeClass,
    getButtonClass,
    applyThemeStyles,
  };
}
