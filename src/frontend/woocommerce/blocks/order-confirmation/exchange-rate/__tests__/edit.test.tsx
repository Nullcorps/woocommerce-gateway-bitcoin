import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { Edit } from '../edit';

// Mock WordPress components
jest.mock('@wordpress/block-editor', () => ({
  useBlockProps: jest.fn(() => ({ className: 'test-block-props' })),
  InspectorControls: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="inspector-controls">{children}</div>
  ),
}));

jest.mock('@wordpress/components', () => ({
  PanelBody: ({ title, children }: { title: string; children: React.ReactNode }) => (
    <div data-testid="panel-body" data-title={title}>
      {children}
    </div>
  ),
  ToggleControl: ({ label, checked, onChange }: any) => (
    <label data-testid="toggle-control">
      {label}
      <input
        type="checkbox"
        checked={checked}
        onChange={(e) => onChange(e.target.checked)}
      />
    </label>
  ),
}));

// Mock the ExchangeRateDisplay component
jest.mock('../exchange-rate-display', () => ({
  ExchangeRateDisplay: ({ showLabel, isPreview }: { showLabel: boolean; isPreview: boolean }) => (
    <div data-testid="exchange-rate-display" data-show-label={showLabel} data-is-preview={isPreview}>
      Mock Exchange Rate Display
    </div>
  ),
}));

describe('Edit Component', () => {
  const defaultProps = {
    attributes: {
      orderId: 0,
      showLabel: true,
    },
    setAttributes: jest.fn(),
    context: {},
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders with default attributes', () => {
    render(<Edit {...defaultProps} />);
    
    expect(screen.getByTestId('inspector-controls')).toBeInTheDocument();
    expect(screen.getByTestId('panel-body')).toHaveAttribute('data-title', 'Exchange Rate Settings');
    expect(screen.getByTestId('toggle-control')).toBeInTheDocument();
    expect(screen.getByTestId('exchange-rate-display')).toBeInTheDocument();
  });

  it('shows label toggle control', () => {
    render(<Edit {...defaultProps} />);
    
    const toggleControl = screen.getByTestId('toggle-control');
    const checkbox = toggleControl.querySelector('input[type="checkbox"]') as HTMLInputElement;
    
    expect(toggleControl).toHaveTextContent('Show label');
    expect(checkbox.checked).toBe(true);
  });

  it('calls setAttributes when showLabel is toggled', () => {
    const setAttributes = jest.fn();
    render(<Edit {...defaultProps} setAttributes={setAttributes} />);
    
    const checkbox = screen.getByRole('checkbox');
    fireEvent.click(checkbox);
    
    expect(setAttributes).toHaveBeenCalledWith({ showLabel: false });
  });

  it('passes showLabel to ExchangeRateDisplay', () => {
    render(<Edit {...defaultProps} attributes={{ orderId: 0, showLabel: false }} />);
    
    const display = screen.getByTestId('exchange-rate-display');
    expect(display).toHaveAttribute('data-show-label', 'false');
    expect(display).toHaveAttribute('data-is-preview', 'true');
  });

  it('displays order ID when orderId > 0', () => {
    render(<Edit {...defaultProps} attributes={{ orderId: 123, showLabel: true }} />);
    
    expect(screen.getByText('Order ID: 123')).toBeInTheDocument();
  });

  it('uses context order ID when available', () => {
    const props = {
      ...defaultProps,
      context: {
        'bh-wp-bitcoin-gateway/orderId': 456,
      },
    };
    
    render(<Edit {...props} />);
    
    expect(screen.getByText('Order ID: 456')).toBeInTheDocument();
    expect(screen.getByText('(Using order ID from container block)')).toBeInTheDocument();
  });

  it('prioritizes context order ID over attribute order ID', () => {
    const props = {
      ...defaultProps,
      attributes: { orderId: 123, showLabel: true },
      context: {
        'bh-wp-bitcoin-gateway/orderId': 456,
      },
    };
    
    render(<Edit {...props} />);
    
    expect(screen.getByText('Order ID: 456')).toBeInTheDocument();
    expect(screen.queryByText('Order ID: 123')).not.toBeInTheDocument();
  });

  it('shows context message when using context order ID', () => {
    const props = {
      ...defaultProps,
      context: {
        'bh-wp-bitcoin-gateway/orderId': 789,
      },
    };
    
    render(<Edit {...props} />);
    
    expect(screen.getByText('(Using order ID from container block)')).toBeInTheDocument();
  });

  it('does not show order ID when orderId is 0', () => {
    render(<Edit {...defaultProps} />);
    
    expect(screen.queryByText(/Order ID:/)).not.toBeInTheDocument();
  });

  it('shows preview description', () => {
    render(<Edit {...defaultProps} />);
    
    expect(screen.getByText('Preview - actual rate will display on order confirmation pages')).toBeInTheDocument();
  });

  it('applies correct CSS classes and styling', () => {
    render(<Edit {...defaultProps} />);
    
    const description = screen.getByText('Preview - actual rate will display on order confirmation pages');
    expect(description).toHaveClass('description');
    
    // Check inline styles
    const descriptionElement = description as HTMLElement;
    expect(descriptionElement.style.fontSize).toBe('12px');
    expect(descriptionElement.style.opacity).toBe('0.7');
    expect(descriptionElement.style.marginTop).toBe('4px');
  });

  it('handles missing context gracefully', () => {
    const props = {
      ...defaultProps,
      context: {},
    };
    
    render(<Edit {...props} />);
    
    expect(screen.queryByText('(Using order ID from container block)')).not.toBeInTheDocument();
  });

  it('handles undefined context orderId', () => {
    const props = {
      ...defaultProps,
      context: {
        'bh-wp-bitcoin-gateway/orderId': undefined,
      },
    };
    
    render(<Edit {...props} />);
    
    expect(screen.queryByText(/Order ID:/)).not.toBeInTheDocument();
    expect(screen.queryByText('(Using order ID from container block)')).not.toBeInTheDocument();
  });
});