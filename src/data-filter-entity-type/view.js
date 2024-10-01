/**
 * WordPress dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

const { state, actions, callbacks } = store( 'ttft/data-tables', {
	actions: {
		updateEntityType: ( event ) => {
			event.preventDefault();
			const { ref } = getElement();
			state.entityType = ref.getAttribute( 'data-entity-type' );
			// context.entityType = state.entityType;
			// state.thinkTankSelected = state.entityType === 'think_tank';
			// state.donorSelected = state.entityType === 'donor';
			console.log( `entityType: `, ref.getAttribute( 'data-entity-type' ) );
		},
		toggleThinkTank: ( event ) => {
			event.preventDefault();
			const context = getContext();
			const { thinkTankSelected } = state;
			state.thinkTankSelected = !thinkTankSelected;
			state.donorSelected = !state.thinkTankSelected;
			context.thinkTankSelected = state.thinkTankSelected;
			console.log( `thinkTankSelected: `, state.thinkTankSelected, `donorSelected: `, state.donorSelected, );
		},
		toggleDonor: ( event ) => {
			event.preventDefault();
			const context = getContext();
			const { donorSelected } = state;
			state.donorSelected = !donorSelected;
			state.thinkTankSelected = !state.donorSelected;
			context.donorSelected = state.donorSelected;
			console.log( `donorSelected: `, state.donorSelected, `thinkTankSelected: `, state.thinkTankSelected );
		},
	},
	callbacks: {
		logType: () => {
			const { entityType } = state;
			// console.log( `entityType: `, entityType );
		},
	},
} );
