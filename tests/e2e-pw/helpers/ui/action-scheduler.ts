export async function runActionInRow(
	page: any,
	actionSchedulerTableRow: any
) {
	const hookColumn = actionSchedulerTableRow.locator(
		'td[data-colname="Hook"]'
	);
	await hookColumn.hover();

	const runLink = hookColumn.locator( '.run a' );
	if ( ( await runLink.count() ) > 0 ) {
		await runLink.click();
		await page.waitForLoadState( 'networkidle' );
	}
}
