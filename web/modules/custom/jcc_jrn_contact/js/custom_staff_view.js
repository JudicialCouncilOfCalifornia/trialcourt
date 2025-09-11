(function (Drupal) {
  Drupal.behaviors.staffTableHighlight = {
    attach(context) {
      const tables = context.querySelectorAll('.staff-table table', context);
      tables.forEach((table) => {
        const headerRow = table.querySelector('thead tr');
        if (!headerRow) return;

        const bodyRows = table.querySelectorAll('tbody tr');
        const colCount = headerRow.children.length;

        for (let col = 0; col < colCount; col++) {
          const headerCell = headerRow.children[col];

          // Highlight when hovering header cell
          headerCell.addEventListener('mouseenter', () => {
            highlightCol(table, col);
          });
          headerCell.addEventListener('mouseleave', () => {
            resetCol(table, col);
          });

          // Highlight when hovering body cell
          bodyRows.forEach((row) => {
          
            const cell = row.children[col];
            if (!cell) return;
            cell.addEventListener('mouseenter', () => {
              highlightCol(table, col);
            });
            cell.addEventListener('mouseleave', () => {
              resetCol(table, col);
            });
          });
        }
      });

      function highlightCol(table, col) {
        const headerRow = table.querySelector('thead tr');
        const bodyRows = table.querySelectorAll('tbody tr');
        
        // Add class to header
        headerRow.children[col].classList.add('col-highlight-header');

        // Add class to column cells
        bodyRows.forEach((row) => {
            if (row.children[col]) {
            row.children[col].classList.add('col-highlight-cell');
            }
        });
    }

    function resetCol(table, col) {
    const headerRow = table.querySelector('thead tr');
    const bodyRows = table.querySelectorAll('tbody tr');

    // Remove class from header
    headerRow.children[col].classList.remove('col-highlight-header');

    // Remove class from column cells
    bodyRows.forEach((row) => {
        if (row.children[col]) {
        row.children[col].classList.remove('col-highlight-cell');
        }
    });
    }
},
};
})(Drupal);
