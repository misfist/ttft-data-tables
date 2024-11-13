import * as __WEBPACK_EXTERNAL_MODULE__wordpress_interactivity_8e89b257__ from "@wordpress/interactivity";
/******/ var __webpack_modules__ = ({

/***/ "@wordpress/interactivity":
/*!*******************************************!*\
  !*** external "@wordpress/interactivity" ***!
  \*******************************************/
/***/ ((module) => {

module.exports = __WEBPACK_EXTERNAL_MODULE__wordpress_interactivity_8e89b257__;

/***/ })

/******/ });
/************************************************************************/
/******/ // The module cache
/******/ var __webpack_module_cache__ = {};
/******/ 
/******/ // The require function
/******/ function __webpack_require__(moduleId) {
/******/ 	// Check if module is in cache
/******/ 	var cachedModule = __webpack_module_cache__[moduleId];
/******/ 	if (cachedModule !== undefined) {
/******/ 		return cachedModule.exports;
/******/ 	}
/******/ 	// Create a new module (and put it into the cache)
/******/ 	var module = __webpack_module_cache__[moduleId] = {
/******/ 		// no module.id needed
/******/ 		// no module.loaded needed
/******/ 		exports: {}
/******/ 	};
/******/ 
/******/ 	// Execute the module function
/******/ 	__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 
/******/ 	// Return the exports of the module
/******/ 	return module.exports;
/******/ }
/******/ 
/************************************************************************/
/******/ /* webpack/runtime/make namespace object */
/******/ (() => {
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = (exports) => {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/ })();
/******/ 
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!********************************!*\
  !*** ./src/data-table/view.js ***!
  \********************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/interactivity */ "@wordpress/interactivity");
/**
 * WordPress dependencies
 */

const initTable = tableId => {
  const title = document.querySelector('.site-main h1')?.innerText || document.title;
  let USDollar = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  });
  const {
    tableType,
    thinkTank,
    donor,
    donationYear,
    donorType,
    search,
    searchLabel,
    ajaxUrl,
    action,
    nonce,
    pageLength
  } = state;
  const table = new DataTable(tableId, {
    // pageLength: pageLength || 50,
    retrieve: true,
    api: true,
    autoWidth: false,
    processing: true,
    layout: {
      topEnd: {
        search: {
          placeholder: 'Enter keyword...',
          text: state.searchLabel || 'Search',
          processing: true,
          menu: [25, 50, 75, 100]
        }
      },
      topStart: {
        buttons: [{
          extend: 'csvHtml5',
          title: title,
          text: 'Download Data',
          exportOptions: {
            columns: ':visible:not(.noExport)'
          }
        }],
        info: {
          callback: function (s, start, end, max, total, result) {
            return `${max} Records Found`;
          }
        }
      },
      bottomStart: false,
      bottomEnd: {
        paging: {
          type: 'simple_numbers'
        }
      }
    },
    render: {
      currency: function (data, type, row) {
        return USDollar.format(data);
      },
      number: function (data, type, row) {
        return data.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "'");
      }
      // date: function ( data, type, row ) {
      // 	return new Date( data ).toLocaleDateString();
      // },
      // dateRange: function ( data, type, row ) {
      // 	return `${ new Date( data.start ).toLocaleDateString() } - ${ new Date( data.end ).toLocaleDateString() }`;
      // },
      // link: function ( data, type, row ) {
      // 	return `<a href="${ data }" target="_blank">${ data }</a>`;
      // },
      // image: function ( data, type, row ) {
      // 	return `<img src="${ data }" alt="${ row.name }" />`;
      // },
    },
    drawCallback: function (settings) {
      // console.log( `drawCallback: `, settings );
    },
    initComplete: function (settings, json) {
      // console.log( `initComplete: `, settings );
    },
    // formatNumber: function ( toFormat ) {
    // 	console.log( `toFormat: `, toFormat );
    // 	return toFormat.toString().replace( /\B(?=(\d{3})+(?!\d))/g, "'" );
    // },
    headerCallback: function (thead, data, start, end, display) {},
    footerCallback: function (row, data, start, end, display) {}
  });
  return table;
};
const hasStateChanged = () => {
  const [type, setType] = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.useState)(state.donorType);
  const [year, setYear] = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.useState)(state.donationYear);
  const checkStateChanged = () => {
    return type !== state.donorType || year !== state.donationYear;
  };
  (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (checkStateChanged()) {
      setType(state.donorType);
      setYear(state.donationYear);
    }
  }, [state.donorType, state.donationYear]);
  return checkStateChanged();
};
const {
  state,
  actions,
  callbacks
} = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.store)('ttft/data-tables', {
  state: {
    isLoading: false,
    donationYear: 'all',
    donorType: 'all',
    entityType: 'think_tank'
  },
  actions: {
    async renderTable() {
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      try {
        const {
          tableType,
          thinkTank,
          donor,
          donationYear,
          donorType,
          search,
          searchLabel,
          ajaxUrl,
          action,
          nonce
        } = state;
        const formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', nonce);
        formData.append('table_type', tableType);
        formData.append('search_label', searchLabel || 'Search');
        formData.append('donation_year', donationYear || 'all');
        formData.append('donor_type', donorType || 'all');
        formData.append('think_tank', thinkTank);
        formData.append('donor', donor);
        formData.append('search', search || '');
        console.log(`formData: `, formData);
        state.isLoading = true;
        context.isLoaded = false;
        const response = await fetch(ajaxUrl, {
          method: 'POST',
          body: formData
        });
        const responseText = await response.text();
        console.log(`responseText: `, responseText);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        const jsonResponse = JSON.parse(responseText);
        if (jsonResponse.success) {
          state.tableData = jsonResponse.data;

          // debugger;

          /**
           * Maybe not necessary to destroy the table
           */
          // await actions.destroyTableAsync();

          const container = document.getElementById(state.elementId);
          if (container) {
            container.innerHTML = jsonResponse.data;
            if (state.table) {
              state.table.clear();
              state.table = initTable(`#${state.tableId}`);
            }
          }
        } else {
          console.error(`Error fetching table data:`, jsonResponse.data);
        }
      } catch (event) {
        console.error(`catch( event ) renderTable:`, event);
      } finally {
        state.isLoading = false;
        context.isLoaded = true;
      }
    },
    initTable: () => {
      state.table = initTable(`#${state.tableId}`);
    },
    destroyTable: () => {
      state.table.clear().draw();
      state.table.destroy();
    },
    destroyTableAsync: () => {
      return new Promise(resolve => {
        actions.destroyTable();
        resolve();
      });
    },
    setContext: () => {
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      // context.tableType && ( context.tableType = state.tableType );
      // context.donationYear && ( context.donationYear = state.donationYear );
      // context.donorType && ( context.donorType = state.donorType );
      context.searchLabel && (context.searchLabel = state.searchLabel);
    },
    generateSkeletonTable: columns => {
      let rows = '';
      const max = 10;
      for (let rowIndex = 0; rowIndex < max; rowIndex++) {
        rows += '<tr class="row skeleton-row" width="100%">';
        for (let colIndex = 0; colIndex < columns; colIndex++) {
          rows += '<td class="cell skeleton-cell"><div class="loader"></div></td>';
        }
        rows += '</tr>';
      }
      return rows;
    }
  },
  callbacks: {
    renderTable: () => {
      const hasChanged = hasStateChanged();
      (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
        if (hasChanged) {
          actions.renderTable();
        }
      });
    },
    loadAnimation: () => {
      (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
        const container = document.getElementById(state.elementId);
        if (state.isLoading && container) {
          const columns = container.querySelectorAll('thead th').length;
          const skeletonTable = actions.generateSkeletonTable(columns);
          const body = container.querySelector('tbody');
          if (body) {
            body.innerHTML = skeletonTable;
          }
        }
      }, [state.isLoading]);
    },
    initLog: () => {
      // console.log( `Initial State: `, JSON.stringify( state, undefined, 2 )  );
      // const { tableType, donationYear, donorType, isLoaded } = getContext();
      // console.log( `Initial Context: ${tableType}, isLoaded: ${isLoaded}, ${donationYear}, ${donorType}` );
    },
    logTable: () => {
      const {
        tableType,
        thinkTank,
        donor,
        donationYear,
        donorType,
        search,
        isLoading
      } = state;
      console.log(`State: `, tableType, thinkTank, donor, donationYear, donorType, isLoading);
    },
    logState: key => {
      console.log(`key: `, state.key);
    },
    logLoading: () => {
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      console.log(`IS LOADING: `, state.isLoading, context.isLoaded);
    }
  }
});
})();


//# sourceMappingURL=view.js.map