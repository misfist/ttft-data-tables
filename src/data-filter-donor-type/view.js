/**
 * WordPress dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

const { state, actions } = store( 'ttft/data-tables', {
	actions: {
		updateType: () => {
			const context = getContext();
			const { ref } = getElement();
			state.donorType = ref.value;
		}
	},
	callbacks: {
		log: () => {
			console.log( `State: ${ state.donorType }` );
		},
		isSelected: () => {
			const { ref } = getElement();
			return state.donorType == ref.value;
		}
	},
} );
