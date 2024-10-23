/**
 * WordPress dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

const { state, actions, callbacks } = store( 'ttft/data-tables', {
	actions: {
		updateYear: () => {
			const { ref } = getElement();
			state.donationYear = ref.value ?? 'all';
		},
	},
	callbacks: {
		logYear: () => {
			const { donationYear } = state;
			// console.log( `donationYear: `, donationYear );
		},
		isSelected: () => {
			const { ref } = getElement();
			return state.donationYear == ref.value;
		},
	},
} );
