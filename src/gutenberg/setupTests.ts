import '@testing-library/jest-dom';

// Mock @wordpress/private-apis
jest.mock( '@wordpress/private-apis', () => ( {
	__dangerousOptInToUnstableAPIsOnlyForCoreModules: jest.fn( () => ( {
		lock: jest.fn(),
		unlock: jest.fn( () => ( {} ) ),
	} ) ),
} ) );

// Mock @wordpress/data
jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	createRegistrySelector: jest.fn( ( selector ) => selector ),
	createReduxStore: jest.fn( ( name, config ) => ( {
		name,
		reducer: config.reducer,
		actions: config.actions,
		selectors: config.selectors,
	} ) ),
	register: jest.fn(),
	useDispatch: jest.fn( () => ( {
		updateBlockAttributes: jest.fn(),
	} ) ),
} ) );

// Mock Web Worker for tests
class MockWorker {
	url: string;
	onmessage: ( ( event: MessageEvent ) => void ) | null = null;
	onerror: ( ( event: ErrorEvent ) => void ) | null = null;

	constructor( stringUrl: string ) {
		this.url = stringUrl;
	}

	postMessage( msg: any ) {
		// Mock implementation
	}

	addEventListener( type: string, listener: EventListener ) {
		// Mock implementation
	}

	removeEventListener( type: string, listener: EventListener ) {
		// Mock implementation
	}

	terminate() {
		// Mock implementation
	}
}

global.Worker = MockWorker as any;

// Mock the useAnalysis hook to avoid import.meta issues
jest.mock( '../hooks/useAnalysis', () => ( {
	useAnalysis: jest.fn(),
} ) );
