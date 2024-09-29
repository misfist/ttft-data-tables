/**
 * WordPress dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

const { state, actions, callbacks } = store( 'ttft/data-tables', {
	actions: {
		updateType: () => {
			const { ref } = getElement();
			state.donorType = ref.value ?? 'all';
		}
	},
	callbacks: {
		logType: () => {
			const { donorType } = state;
			console.log( `donorType: `, donorType );
		},
		isSelected: () => {
			const { ref } = getElement();
			return state.donorType == ref.value;
		}
	},
} );
