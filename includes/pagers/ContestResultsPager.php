<?php

class ContestResultsPager extends TablePager {

	/**
	 * Provides all parameters needed for the main paged query. It returns
	 * an associative array with the following elements:
	 *    tables => Table(s) for passing to Database::select()
	 *    fields => Field(s) for passing to Database::select(), may be *
	 *    conds => WHERE conditions
	 *    options => option array
	 *    join_conds => JOIN conditions
	 *
	 * @return array
	 */
	function getQueryInfo() {
		// TODO: Implement getQueryInfo() method.
	}

	/**
	 * Return true if the named field should be sortable by the UI, false
	 * otherwise
	 *
	 * @param string $field
	 */
	function isFieldSortable( $field ) {
		// TODO: Implement isFieldSortable() method.
	}

	/**
	 * Format a table cell. The return value should be HTML, but use an empty
	 * string not &#160; for empty cells. Do not include the <td> and </td>.
	 *
	 * The current result row is available as $this->mCurrentRow, in case you
	 * need more context.
	 *
	 * @protected
	 *
	 * @param string $name The database field name
	 * @param string $value The value retrieved from the database
	 */
	function formatValue( $name, $value ) {
		// TODO: Implement formatValue() method.
	}

	/**
	 * The database field name used as a default sort order.
	 *
	 * Note that this field will only be sorted on if isFieldSortable returns
	 * true for this field. If not (e.g. paginating on multiple columns), this
	 * should return empty string, and getIndexField should be overridden.
	 *
	 * @protected
	 *
	 * @return string
	 */
	function getDefaultSort() {
		// TODO: Implement getDefaultSort() method.
	}

	/**
	 * An array mapping database field names to a textual description of the
	 * field name, for use in the table header. The description should be plain
	 * text, it will be HTML-escaped later.
	 *
	 * @return array
	 */
	function getFieldNames() {
		// TODO: Implement getFieldNames() method.
	}
}