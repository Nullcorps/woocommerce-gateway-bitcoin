import config from "../../../playwright.config";
import { Response } from "node/globals";

async function fetchBitcoinAddresses(status?: String): Promise<Response> {
  const baseURL: string = config.use.baseURL;
  var fullUrl = `${baseURL}/wp-json/wp/v2/bh-bitcoin-address`;
  if(status) {
    fullUrl += `?status=${status}`;
  }
  return await fetch(fullUrl);
}

export async function getBitcoinAddressCount(status?: String): Promise<number> {

  const response = await fetchBitcoinAddresses(status);

  return parseInt(response.headers.get('X-WP-Total'));
}


export async function deleteBitcoinAddresses(deleteCount: number, status?: String) {

  const response = await fetchBitcoinAddresses(status);

  const items = await response.json();
  const existingCount = parseInt(response.headers.get('X-WP-Total'));

  const baseURL: string = config.use.baseURL;

  var fullUrl = `${baseURL}/wp-json/wp/v2/bh-bitcoin-address`;

  var post_id;
  for (var i = 0; i < deleteCount && i < existingCount; i++) {

    // iterate over response to get post_id

    post_id = items[i].id

    fullUrl += `/${post_id}`;

    await fetch(fullUrl, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json'
      }
    });
  }
}
