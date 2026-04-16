# Account Limits and API Usage

## Default API rate limits

New accounts start on the Starter plan with the following limits:

- 1,000 API calls per day
- 10 requests per second
- Maximum payload size: 1 MB per request

## How do I check my current usage?

Usage data is available in real time under Settings > API > Usage Dashboard. You can filter by day, week, or month.

## What happens when I hit the rate limit?

Requests that exceed your rate limit receive a `429 Too Many Requests` response. Implement exponential backoff in your client and retry after the interval specified in the `Retry-After` header.

## Increasing your limits

To increase your API limits, upgrade to a higher plan from Settings > Billing > Plan. Enterprise plan limits are negotiated individually. Contact your account manager or submit a request through the support portal.

## Data retention

- Logs are retained for 30 days on Starter, 90 days on Pro, 1 year on Enterprise.
- Exported data is your responsibility to store after the retention window.
