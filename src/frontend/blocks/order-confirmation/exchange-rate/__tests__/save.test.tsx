import React from 'react';
import { render, screen } from '@testing-library/react';
import { Save } from '../save';

// Mock WordPress block editor
jest.mock('@wordpress/block-editor', () => ({
  useBlockProps: {
    save: jest.fn(() => ({ className: 'test-save-block-props' })),
  },
}));

// Mock the ExchangeRateDisplay component
jest.mock('../exchange-rate-display', () => ({
  ExchangeRateDisplay: ({ showLabel }: { showLabel: boolean }) => (
    <div data-testid="exchange-rate-display" data-show-label={showLabel}>
      Mock Exchange Rate Display
    </div>
  ),
}));

describe('Save Component', () => {
  const defaultProps = {
    attributes: {
      orderId: 123,
      showLabel: true,
    },
  };

  it('renders ExchangeRateDisplay with correct showLabel prop', () => {
    render(<Save {...defaultProps} />);
    
    const display = screen.getByTestId('exchange-rate-display');
    expect(display).toBeInTheDocument();
    expect(display).toHaveAttribute('data-show-label', 'true');
  });

  it('passes showLabel false correctly', () => {
    const props = {
      attributes: {
        orderId: 456,
        showLabel: false,
      },
    };
    
    render(<Save {...props} />);
    
    const display = screen.getByTestId('exchange-rate-display');
    expect(display).toHaveAttribute('data-show-label', 'false');
  });

  it('renders with block props and correct CSS class', () => {
    render(<Save {...defaultProps} />);
    
    const container = screen.getByTestId('exchange-rate-display').parentElement;
    expect(container).toHaveClass('test-save-block-props');
  });

  it('does not pass orderId to ExchangeRateDisplay', () => {
    render(<Save {...defaultProps} />);
    
    const display = screen.getByTestId('exchange-rate-display');
    expect(display).not.toHaveAttribute('data-order-id');
  });

  it('handles missing orderId attribute', () => {
    const props = {
      attributes: {
        orderId: 0,
        showLabel: true,
      },
    };
    
    render(<Save {...props} />);
    
    expect(screen.getByTestId('exchange-rate-display')).toBeInTheDocument();
  });

  it('uses only showLabel attribute, ignoring orderId', () => {
    const props = {
      attributes: {
        orderId: 999,
        showLabel: false,
      },
    };
    
    render(<Save {...props} />);
    
    const display = screen.getByTestId('exchange-rate-display');
    expect(display).toHaveAttribute('data-show-label', 'false');
    expect(display).not.toHaveAttribute('data-order-id');
  });
});