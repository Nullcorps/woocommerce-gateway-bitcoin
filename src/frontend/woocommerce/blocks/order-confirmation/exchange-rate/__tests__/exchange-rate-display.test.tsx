import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import { ExchangeRateDisplay } from '../exchange-rate-display';

// Mock fetch globally
global.fetch = jest.fn();

// Mock window.location with jest
const mockLocation = {
  pathname: '/checkout/order-received/123/',
  search: '?order-received=123&key=wc_order_abcd',
  hash: '',
};

// Mock wpApiSettings
(window as any).wpApiSettings = {
  nonce: 'test-nonce-123',
};

describe('ExchangeRateDisplay', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (global.fetch as jest.Mock).mockClear();
    
    // Reset location mock before each test
    Object.defineProperty(window, 'location', {
      value: { ...mockLocation },
      writable: true,
      configurable: true,
    });
  });

  it('shows loading state initially when not in preview mode', () => {
    render(<ExchangeRateDisplay isPreview={false} />);
    expect(screen.getByText('Loading...')).toBeInTheDocument();
  });

  it('shows preview rate when in preview mode', () => {
    render(<ExchangeRateDisplay isPreview={true} />);
    expect(screen.getByText('1 BTC = $45,000.00 USD')).toBeInTheDocument();
    expect(screen.getByText('Exchange rate at time of order:')).toBeInTheDocument();
  });

  it('hides label when showLabel is false', () => {
    render(<ExchangeRateDisplay showLabel={false} isPreview={true} />);
    expect(screen.queryByText('Exchange rate at time of order:')).not.toBeInTheDocument();
    expect(screen.getByText('1 BTC = $45,000.00 USD')).toBeInTheDocument();
  });

  it('extracts order ID from pathname correctly', async () => {
    const mockOrder = {
      payment_method: 'bh_wp_bitcoin_gateway',
      meta_data: [
        {
          key: 'bh_wp_bitcoin_gateway_exchange_rate_at_time_of_purchase',
          value: '$42,000.00 USD'
        }
      ]
    };

    (global.fetch as jest.Mock).mockResolvedValueOnce({
      ok: true,
      json: jest.fn().mockResolvedValueOnce(mockOrder),
    });

    render(<ExchangeRateDisplay isPreview={false} />);

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalledWith('/wp-json/wc/v3/orders/123', {
        credentials: 'include',
        headers: {
          'X-WP-Nonce': 'test-nonce-123',
        },
      });
    });

    await waitFor(() => {
      expect(screen.getByText('1 BTC = $42,000.00 USD')).toBeInTheDocument();
    });
  });

  it('extracts order ID from hash when not in pathname', async () => {
    // Change location to not have order ID in pathname
    Object.defineProperty(window, 'location', {
      value: {
        pathname: '/checkout/',
        search: '',
        hash: '#order-received/456',
      },
      writable: true,
    });

    const mockOrder = {
      payment_method: 'bh_wp_bitcoin_gateway',
      meta_data: [
        {
          key: 'bh_wp_bitcoin_gateway_exchange_rate_at_time_of_purchase',
          value: '$48,000.00 USD'
        }
      ]
    };

    (global.fetch as jest.Mock).mockResolvedValueOnce({
      ok: true,
      json: jest.fn().mockResolvedValueOnce(mockOrder),
    });

    render(<ExchangeRateDisplay isPreview={false} />);

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalledWith('/wp-json/wc/v3/orders/456', {
        credentials: 'include',
        headers: {
          'X-WP-Nonce': 'test-nonce-123',
        },
      });
    });
  });

  it('returns null when no order ID found', async () => {
    Object.defineProperty(window, 'location', {
      value: {
        pathname: '/checkout/',
        search: '',
        hash: '',
      },
      writable: true,
      configurable: true,
    });

    render(<ExchangeRateDisplay isPreview={false} />);

    await waitFor(() => {
      expect(screen.queryByText(/Loading/)).not.toBeInTheDocument();
    });

    expect(global.fetch).not.toHaveBeenCalled();
  });

  it('returns null when order is not a Bitcoin Gateway order', async () => {
    const mockOrder = {
      payment_method: 'stripe',
      meta_data: []
    };

    (global.fetch as jest.Mock).mockResolvedValueOnce({
      ok: true,
      json: jest.fn().mockResolvedValueOnce(mockOrder),
    });

    render(<ExchangeRateDisplay isPreview={false} />);

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalled();
    });

    await waitFor(() => {
      expect(screen.queryByText(/Loading/)).not.toBeInTheDocument();
    });

    expect(screen.queryByText(/1 BTC =/)).not.toBeInTheDocument();
  });

  it('returns null when exchange rate meta is not found', async () => {
    window.location.pathname = '/checkout/order-received/123/';

    const mockOrder = {
      payment_method: 'bh_wp_bitcoin_gateway',
      meta_data: [
        {
          key: 'some_other_meta',
          value: 'some_value'
        }
      ]
    };

    (global.fetch as jest.Mock).mockResolvedValueOnce({
      ok: true,
      json: jest.fn().mockResolvedValueOnce(mockOrder),
    });

    render(<ExchangeRateDisplay isPreview={false} />);

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalled();
    });

    await waitFor(() => {
      expect(screen.queryByText(/1 BTC =/)).not.toBeInTheDocument();
    });
  });

  it('handles fetch errors gracefully', async () => {
    window.location.pathname = '/checkout/order-received/123/';

    (global.fetch as jest.Mock).mockRejectedValueOnce(new Error('Network error'));

    const consoleSpy = jest.spyOn(console, 'error').mockImplementation();

    render(<ExchangeRateDisplay isPreview={false} />);

    await waitFor(() => {
      expect(consoleSpy).toHaveBeenCalledWith('Error fetching exchange rate:', expect.any(Error));
    });

    await waitFor(() => {
      expect(screen.queryByText(/1 BTC =/)).not.toBeInTheDocument();
    });

    consoleSpy.mockRestore();
  });

  it('handles non-ok response status', async () => {
    window.location.pathname = '/checkout/order-received/123/';

    (global.fetch as jest.Mock).mockResolvedValueOnce({
      ok: false,
      status: 404,
    });

    render(<ExchangeRateDisplay isPreview={false} />);

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalled();
    });

    await waitFor(() => {
      expect(screen.queryByText(/1 BTC =/)).not.toBeInTheDocument();
    });
  });

  it('applies correct CSS classes', () => {
    render(<ExchangeRateDisplay isPreview={true} />);
    
    const container = screen.getByText('1 BTC = $45,000.00 USD').closest('div');
    expect(container).toHaveClass('bh-wp-bitcoin-gateway-exchange-rate-block');
    
    expect(screen.getByText('Exchange rate at time of order:')).toHaveClass('exchange-rate-label');
    expect(screen.getByText('1 BTC = $45,000.00 USD')).toHaveClass('exchange-rate-value');
  });
});