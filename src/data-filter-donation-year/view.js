/**
 * WordPress dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

const { state, actions } = store( 'ttft/data-tables', {
	actions: {
		updateYear: () => {
			const context = getContext();
			const { ref } = getElement();
			state.donationYear = ref.value;
		}
	},
	callbacks: {
		log: () => {
			console.log( `State: ${ state.donationYear }` );
		},
		isSelected: () => {
			const { ref } = getElement();
			return state.donationYear == ref.value;
		}
	},
} );
