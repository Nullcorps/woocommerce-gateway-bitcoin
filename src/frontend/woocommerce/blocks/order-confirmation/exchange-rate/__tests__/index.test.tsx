import { registerBlockType } from '@wordpress/blocks';

// Mock WordPress dependencies
jest.mock('@wordpress/blocks', () => ({
  registerBlockType: jest.fn(),
}));

jest.mock('@wordpress/i18n', () => ({
  __: jest.fn((text: string) => text),
}));

// Mock the components
jest.mock('../edit', () => ({
  Edit: jest.fn(() => null),
}));

jest.mock('../save', () => ({
  Save: jest.fn(() => null),
}));

// Mock metadata
jest.mock('../block.json', () => ({
  name: 'bh-wp-bitcoin-gateway/exchange-rate',
  title: 'Bitcoin Exchange Rate',
  category: 'bitcoin-gateway',
  attributes: {
    showLabel: {
      type: 'boolean',
      default: true,
    },
    orderId: {
      type: 'number',
      default: 0,
    },
  },
}));

describe('Exchange Rate Block Registration', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('registers the block type with correct parameters', () => {
    // Import the index file to trigger registration
    require('../index');

    expect(registerBlockType).toHaveBeenCalledTimes(1);
    
    const [blockName, blockConfig] = (registerBlockType as jest.Mock).mock.calls[0];
    
    expect(blockName).toBe('bh-wp-bitcoin-gateway/exchange-rate');
    expect(blockConfig).toMatchObject({
      name: 'bh-wp-bitcoin-gateway/exchange-rate',
      title: 'Bitcoin Exchange Rate',
      category: 'bitcoin-gateway',
      attributes: {
        showLabel: {
          type: 'boolean',
          default: true,
        },
        orderId: {
          type: 'number',
          default: 0,
        },
      },
      save: expect.any(Function),
    });
  });

  it('sets save to a function that returns null', () => {
    require('../index');
    
    const [, blockConfig] = (registerBlockType as jest.Mock).mock.calls[0];
    expect(typeof blockConfig.save).toBe('function');
    expect(blockConfig.save()).toBeNull();
  });

  it('includes edit component from imported Edit', () => {
    const { Edit } = require('../edit');
    require('../index');
    
    const [, blockConfig] = (registerBlockType as jest.Mock).mock.calls[0];
    expect(blockConfig.edit).toBe(Edit);
  });

  it('spreads metadata into block configuration', () => {
    const metadata = require('../block.json');
    require('../index');
    
    const [, blockConfig] = (registerBlockType as jest.Mock).mock.calls[0];
    
    // Check that metadata properties are spread into the config
    expect(blockConfig.name).toBe(metadata.name);
    expect(blockConfig.title).toBe(metadata.title);
    expect(blockConfig.category).toBe(metadata.category);
    expect(blockConfig.attributes).toBe(metadata.attributes);
  });
});