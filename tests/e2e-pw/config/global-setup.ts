/**
 * External dependencies
 */
import { chromium, FullConfig } from '@playwright/test';

async function globalSetup( config: FullConfig ) {
	const browser = await chromium.launch();
	const page = await browser.newPage();
	//
	// // do your login:
	// await page.goto("/login");
	// await page.click("text=Log in with Google account");
	// await page.fill(
	//   "id=identifierId",
	//   LoginAutomationCredentials.USER
	// );
	// await page.click('button[jsname="LgbsSe"]');
	// await page.fill(
	//   'input[type="password"]',
	//   LoginAutomationCredentials.PASSWORD
	// );
	// await page.click('button[jsname="LgbsSe"]');
	// const otp = authenticator.generateToken(
	//   LoginAutomationCredentials.TOKEN
	// );
	// await page.fill("id=totpPin", otp);
	// await page.click('button[jsname="LgbsSe"]');

	// see below for further discussion
	// const { baseURL, storageState } = config.projects[0].use;
	// await page.context().storageState({ path: storageState as string });
	// await browser.close();
}

export default globalSetup;
