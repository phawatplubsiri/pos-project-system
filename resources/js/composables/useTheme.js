/**
 * useTheme Composable
 * 
 * Provides access to theme colors and utility functions
 * for consistent theming across Vue components
 */

export function useTheme() {
  // Color palette object
  const colors = {
    // Primary / Brand (Serious & Modern)
    primary: '#1F2937',
    primaryDark: '#111827',
    primaryLight: '#374151',
    primaryHover: '#111827',

    // Action / Accent (Professional Green)
    action: '#059669',
    actionHover: '#047857',

    // Highlight / Warning (Amber)
    highlight: '#D97706',
    highlightLight: '#FEF3C7',

    // Status Colors
    success: '#10B981',
    warning: '#F59E0B',
    danger: '#DC2626',
    disabled: '#9CA3AF',
    info: '#3B82F6', // Blue for info in the new theme

    // Text
    textPrimary: '#111827',
    textSecondary: '#4B5563',
    textWhite: '#FFFFFF',
    textPrice: '#1F2937',

    // Backgrounds
    bgPrimary: '#F3F4F6',
    bgCard: '#FFFFFF',
    bgHover: '#ECFDF5',

    // Borders
    divider: '#E5E7EB',
    border: '#D1D5DB',
    borderLight: '#F3F4F6',
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
