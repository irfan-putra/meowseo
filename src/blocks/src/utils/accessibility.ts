/**
 * Accessibility utilities for MeowSEO blocks
 */

/**
 * Generate unique ID for ARIA labels
 */
export const generateUniqueId = (prefix: string): string => {
  return `${prefix}-${Math.random().toString(36).substr(2, 9)}`;
};

/**
 * Create accessible heading with proper hierarchy
 */
export const createAccessibleHeading = (
  level: 1 | 2 | 3 | 4 | 5 | 6,
  text: string,
  className?: string
): JSX.Element => {
  const HeadingTag = `h${level}` as keyof JSX.IntrinsicElements;
  return (
    <HeadingTag className={className} role="heading" aria-level={level}>
      {text}
    </HeadingTag>
  );
};

/**
 * Create accessible button with keyboard support
 */
export const createAccessibleButton = (
  onClick: () => void,
  label: string,
  ariaLabel?: string
): JSX.Element => {
  return (
    <button
      onClick={onClick}
      aria-label={ariaLabel || label}
      type="button"
      className="meowseo-block-button"
    >
      {label}
    </button>
  );
};

/**
 * Create accessible list with proper ARIA attributes
 */
export const createAccessibleList = (
  items: Array<{ id: string; label: string }>,
  role: 'list' | 'listbox' = 'list'
): JSX.Element => {
  return (
    <ul role={role} className="meowseo-block-list">
      {items.map((item) => (
        <li key={item.id} role={role === 'listbox' ? 'option' : undefined}>
          {item.label}
        </li>
      ))}
    </ul>
  );
};

/**
 * Announce dynamic content changes to screen readers
 */
export const announceToScreenReader = (message: string): void => {
  const announcement = document.createElement('div');
  announcement.setAttribute('role', 'status');
  announcement.setAttribute('aria-live', 'polite');
  announcement.setAttribute('aria-atomic', 'true');
  announcement.className = 'screen-reader-text';
  announcement.textContent = message;
  document.body.appendChild(announcement);
  setTimeout(() => announcement.remove(), 1000);
};
