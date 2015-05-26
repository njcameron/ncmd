(function ($, Drupal, window) {

  "use strict";

  /**
   * Attach the tableResponsive function to Drupal.behaviors.
   */
  Drupal.behaviors.tableResponsive = {
    attach: function (context, settings) {
      var $tables = $(context).find('table.responsive-enabled').once('tableresponsive');
      if ($tables.length) {
        var il = $tables.length;
        for (var i = 0; i < il; i++) {
          TableResponsive.tables.push(new TableResponsive($tables[i]));
        }
      }
    }
  };

  /**
   * The TableResponsive object optimizes table presentation for all screen sizes.
   *
   * A responsive table hides columns at small screen sizes, leaving the most
   * important columns visible to the end user. Users should not be prevented from
   * accessing all columns, however. This class adds a toggle to a table with
   * hidden columns that exposes the columns. Exposing the columns will likely
   * break layouts, but it provides the user with a means to access data, which
   * is a guiding principle of responsive design.
   */
  function TableResponsive(table) {
    this.table = table;
    this.$table = $(table);
    this.showText = Drupal.t('Show all columns');
    this.hideText = Drupal.t('Hide lower priority columns');
    // Store a reference to the header elements of the table so that the DOM is
    // traversed only once to find them.
    this.$headers = this.$table.find('th');
    // Add a link before the table for users to show or hide weight columns.
    this.$link = $('<button type="button" class="link tableresponsive-toggle"></button>')
      .attr('title', Drupal.t('Show table cells that were hidden to make the table fit within a small screen.'))
      .on('click', $.proxy(this, 'eventhandlerToggleColumns'));

    this.$table.before($('<div class="tableresponsive-toggle-columns"></div>').append(this.$link));

    // Attach a resize handler to the window.
    $(window)
      .on('resize.tableresponsive', $.proxy(this, 'eventhandlerEvaluateColumnVisibility'))
      .trigger('resize.tableresponsive');
  }

  /**
   * Extend the TableResponsive function with a list of managed tables.
   */
  $.extend(TableResponsive, {
    tables: []
  });

  /**
   * Associates an action link with the table that will show hidden columns.
   *
   * Columns are assumed to be hidden if their header has the class priority-low
   * or priority-medium.
   */
  $.extend(TableResponsive.prototype, {
    eventhandlerEvaluateColumnVisibility: function (e) {
      var pegged = parseInt(this.$link.data('pegged'), 10);
      var hiddenLength = this.$headers.filter('.priority-medium:hidden, .priority-low:hidden').length;
      // If the table has hidden columns, associate an action link with the table
      // to show the columns.
      if (hiddenLength > 0) {
        this.$link.show().text(this.showText);
      }
      // When the toggle is pegged, its presence is maintained because the user
      // has interacted with it. This is necessary to keep the link visible if the
      // user adjusts screen size and changes the visibility of columns.
      if (!pegged && hiddenLength === 0) {
        this.$link.hide().text(this.hideText);
      }
    },
    // Toggle the visibility of columns classed with either 'priority-low' or
    // 'priority-medium'.
    eventhandlerToggleColumns: function (e) {
      e.preventDefault();
      var self = this;
      var $hiddenHeaders = this.$headers.filter('.priority-medium:hidden, .priority-low:hidden');
      this.$revealedCells = this.$revealedCells || $();
      // Reveal hidden columns.
      if ($hiddenHeaders.length > 0) {
        $hiddenHeaders.each(function (index, element) {
          var $header = $(this);
          var position = $header.prevAll('th').length;
          self.$table.find('tbody tr').each(function () {
            var $cells = $(this).find('td').eq(position);
            $cells.show();
            // Keep track of the revealed cells, so they can be hidden later.
            self.$revealedCells = $().add(self.$revealedCells).add($cells);
          });
          $header.show();
          // Keep track of the revealed headers, so they can be hidden later.
          self.$revealedCells = $().add(self.$revealedCells).add($header);
        });
        this.$link.text(this.hideText).data('pegged', 1);
      }
      // Hide revealed columns.
      else {
        this.$revealedCells.hide();
        // Strip the 'display:none' declaration from the style attributes of
        // the table cells that .hide() added.
        this.$revealedCells.each(function (index, element) {
          var $cell = $(this);
          var properties = $cell.attr('style').split(';');
          var newProps = [];
          // The hide method adds display none to the element. The element should
          // be returned to the same state it was in before the columns were
          // revealed, so it is necessary to remove the display none
          // value from the style attribute.
          var match = /^display\s*\:\s*none$/;
          for (var i = 0; i < properties.length; i++) {
            var prop = properties[i];
            prop.trim();
            // Find the display:none property and remove it.
            var isDisplayNone = match.exec(prop);
            if (isDisplayNone) {
              continue;
            }
            newProps.push(prop);
          }
          // Return the rest of the style attribute values to the element.
          $cell.attr('style', newProps.join(';'));
        });
        this.$link.text(this.showText).data('pegged', 0);
        // Refresh the toggle link.
        $(window).trigger('resize.tableresponsive');
      }
    }
  });
  // Make the TableResponsive object available in the Drupal namespace.
  Drupal.TableResponsive = TableResponsive;

})(jQuery, Drupal, window);
;
(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.tableSelect = {
    attach: function (context, settings) {
      // Select the inner-most table in case of nested tables.
      $(context).find('th.select-all').closest('table').once('table-select').each(Drupal.tableSelect);
    }
  };

  Drupal.tableSelect = function () {
    // Do not add a "Select all" checkbox if there are no rows with checkboxes in the table
    if ($(this).find('td input[type="checkbox"]').length === 0) {
      return;
    }

    // Keep track of the table, which checkbox is checked and alias the settings.
    var table = this;
    var checkboxes;
    var lastChecked;
    var $table = $(table);
    var strings = {'selectAll': Drupal.t('Select all rows in this table'), 'selectNone': Drupal.t('Deselect all rows in this table')};
    var updateSelectAll = function (state) {
      // Update table's select-all checkbox (and sticky header's if available).
      $table.prev('table.sticky-header').addBack().find('th.select-all input[type="checkbox"]').each(function () {
        $(this).attr('title', state ? strings.selectNone : strings.selectAll);
        this.checked = state;
      });
    };

    // Find all <th> with class select-all, and insert the check all checkbox.
    $table.find('th.select-all').prepend($('<input type="checkbox" class="form-checkbox" />').attr('title', strings.selectAll)).on('click', function (event) {
      if ($(event.target).is('input[type="checkbox"]')) {
        // Loop through all checkboxes and set their state to the select all checkbox' state.
        checkboxes.each(function () {
          this.checked = event.target.checked;
          // Either add or remove the selected class based on the state of the check all checkbox.
          $(this).closest('tr').toggleClass('selected', this.checked);
        });
        // Update the title and the state of the check all box.
        updateSelectAll(event.target.checked);
      }
    });

    // For each of the checkboxes within the table that are not disabled.
    checkboxes = $table.find('td input[type="checkbox"]:enabled').on('click', function (e) {
      // Either add or remove the selected class based on the state of the check all checkbox.
      $(this).closest('tr').toggleClass('selected', this.checked);

      // If this is a shift click, we need to highlight everything in the range.
      // Also make sure that we are actually checking checkboxes over a range and
      // that a checkbox has been checked or unchecked before.
      if (e.shiftKey && lastChecked && lastChecked !== e.target) {
        // We use the checkbox's parent TR to do our range searching.
        Drupal.tableSelectRange($(e.target).closest('tr')[0], $(lastChecked).closest('tr')[0], e.target.checked);
      }

      // If all checkboxes are checked, make sure the select-all one is checked too, otherwise keep unchecked.
      updateSelectAll((checkboxes.length === checkboxes.filter(':checked').length));

      // Keep track of the last checked checkbox.
      lastChecked = e.target;
    });

    // If all checkboxes are checked on page load, make sure the select-all one
    // is checked too, otherwise keep unchecked.
    updateSelectAll((checkboxes.length === checkboxes.filter(':checked').length));
  };

  Drupal.tableSelectRange = function (from, to, state) {
    // We determine the looping mode based on the order of from and to.
    var mode = from.rowIndex > to.rowIndex ? 'previousSibling' : 'nextSibling';

    // Traverse through the sibling nodes.
    for (var i = from[mode]; i; i = i[mode]) {
      var $i;
      // Make sure that we're only dealing with elements.
      if (i.nodeType !== 1) {
        continue;
      }
      $i = $(i);
      // Either add or remove the selected class based on the state of the target checkbox.
      $i.toggleClass('selected', state);
      $i.find('input[type="checkbox"]').prop('checked', state);

      if (to.nodeType) {
        // If we are at the end of the range, stop.
        if (i === to) {
          break;
        }
      }
      // A faster alternative to doing $(i).filter(to).length.
      else if ($.filter(to, [i]).r.length) {
        break;
      }
    }
  };

})(jQuery, Drupal);
;
