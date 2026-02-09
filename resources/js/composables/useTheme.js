/**
 * useTheme Composable
 * 
 * Provides access to theme colors and utility functions
 * for consistent theming across Vue components
 */

export function useTheme() {
  // Color palette object
  const colors = {
    // Primary / Brand (Navbar, Header, Total)
    primary: '#6B4F3F',
    primaryDark: '#4B3621',
    primaryLight: '#8B6B5D',
    primaryHover: '#5A4235',

    // Action / Accent (Buttons, Active)
    action: '#4CAF8E',
    actionHover: '#3F9B7A',

    // Highlight
    highlight: '#F2C94C',
    highlightLight: '#FFF3C4',

    // Status Colors
    success: '#22C55E',
    warning: '#F59E0B',
    danger: '#E5533D',
    disabled: '#9CA3AF',
    info: '#F2C94C',

    // Text
    textPrimary: '#2E2E2E',
    textSecondary: '#6B7280',
    textWhite: '#FFFFFF',
    textPrice: '#4B3621',

    // Backgrounds
    bgPrimary: '#F6F5F2',
    bgCard: '#ECEAE6',
    bgHover: '#D1FAE5', // Selected row color

    // Borders
    divider: '#D1D5DB',
    border: '#E5E7EB',
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
