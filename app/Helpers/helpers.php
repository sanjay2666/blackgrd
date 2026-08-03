<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;

if (! function_exists('set_message')) {
    function set_message($message, $messageClass = 'successClass')
    {
        Session::put('message', $message);
        Session::put('messageClass', $messageClass);
    }
}

if (! function_exists('remove_message')) {
    function remove_message($msgvar)
    {
        Session::forget($msgvar);
        Session::forget('messageClass');
    }
}

if (! function_exists('currentFinancialYear')) {
    function currentFinancialYear()
    {
        $year = (int) date('Y');
        $month = (int) date('n');
        $startYear = $month >= 4 ? $year : $year - 1;
        $endYear = $startYear + 1;

        return substr((string) $startYear, -2) . substr((string) $endYear, -2);
    }
}

if (! function_exists('display_message')) {
    function display_message($msgvar)
    {
        $message = '';

        if (Session::has($msgvar)) {
            $messageText = e(Session::get($msgvar));

            if (Session::get('messageClass') == 'successClass') {
                $message = '<div class="alert alert-block alert-success"><button type="button" class="close" data-dismiss="alert" aria-label="Close" style="color:#fff; opacity:1; text-shadow:none; font-size:24px; line-height:20px;"><span aria-hidden="true">&times;</span></button><i class="glyphicon glyphicon-ok"></i> '.$messageText.'</div>';
            } elseif (Session::get('messageClass') == 'infoClass') {
                $message = '<div class="alert alert-block alert-info"><button type="button" class="close" data-dismiss="alert" aria-label="Close" style="color:#fff; opacity:1; text-shadow:none; font-size:24px; line-height:20px;"><span aria-hidden="true">&times;</span></button><i class="fa fa-comment"></i> '.$messageText.'</div>';
            } else {
                $message = '<div class="alert alert-block alert-danger"><button type="button" class="close" data-dismiss="alert" aria-label="Close" style="color:#fff; opacity:1; text-shadow:none; font-size:24px; line-height:20px;"><span aria-hidden="true">&times;</span></button><i class="ace-icon fa fa-times"></i> '.$messageText.'</div>';
            }
        }

        remove_message($msgvar);

        return $message;
    }
}

if (! function_exists('getIp')) {
    function getIp()
    {
        $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

        foreach ($keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);

                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return request()->ip();
    }
}

if (! function_exists('mailBody')) {
    function mailBody($bodypart)
    {
        $year = date('Y');
        $logo = '__LOOMEXA_EMAIL_LOGO__';

        $data = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Loomexa</title>
        </head>
        <body>
        <table width="600" border="0" cellspacing="1" cellpadding="3" align="center" style="border:1px solid #d6d6d6; font:normal 12px/16px Arial, Helvetica, sans-serif; color:#818181;">
          <tr>
            <td align="center" valign="top" style="height:58px; border-bottom:3px solid #eeefef; background-color:#f9f9f9; text-align:center; padding:14px 0;"><img width="212" src="'.$logo.'" alt="Loomexa" style="display:inline-block; border:0; outline:none; text-decoration:none;" /></td>
          </tr>
          <tr>
            <td align="left" valign="top" style="padding:10px 20px 20px 20px; color:#4b4b4b;"><table width="100%" border="0" cellspacing="2" cellpadding="0">
              <tr>
                <td align="left" valign="top">'.$bodypart.'</td>
              </tr>
            </table></td>
          </tr>
          <tr>
            <td align="center" valign="middle" style="height:50px; background-color:#4F595B; color:#fff">Copyright &copy;'.$year.' Loomexa, All Rights Reserved.</td>
          </tr>
        </table>
        </body>
        </html>';

        return $data;
    }
}

if (! function_exists('sendMail')) {
    function sendMail($to = '', $subject = '', $body = '', $fromname = '', $type = '', $replyto = '', $bcc = '', $cc = '', $attachments = [])
    {
        if (empty($type)) {
            $type = 'html';
        }

        if (empty($fromname)) {
            $fromname = config('app.name');
        }

        try {
            $recipients = normalizeMailAddresses($to);

            $send = function ($message) use ($recipients, $subject, $fromname, $replyto, $bcc, $cc, $attachments) {
                $message->to($recipients)
                    ->from(config('mail.from.address'), $fromname)
                    ->subject($subject);

                $replyToAddresses = normalizeMailAddresses($replyto);
                if (! empty($replyToAddresses)) {
                    $message->replyTo($replyToAddresses);
                }

                $ccAddresses = normalizeMailAddresses($cc);
                if (! empty($ccAddresses)) {
                    $message->cc($ccAddresses);
                }

                $bccAddresses = normalizeMailAddresses($bcc);
                if (! empty($bccAddresses)) {
                    $message->bcc($bccAddresses);
                }

                foreach (normalizeMailAttachments($attachments) as $attachment) {
                    $message->attach($attachment['path'], $attachment['options']);
                }
            };

            if ($type == 'plain') {
                Mail::raw($body, $send);
            } else {
                Mail::send([], [], function ($message) use ($send, $body) {
                    $send($message);

                    $logoPath = public_path('assets/brand/loomexa-logo.png');
                    $bodyHtml = $body;

                    if (is_file($logoPath)) {
                        $logoUrl = $message->embed($logoPath);
                        $bodyHtml = str_replace('__LOOMEXA_EMAIL_LOGO__', $logoUrl, $bodyHtml);
                    }

                    $message->html($bodyHtml);
                });
            }

            return 'Message sent!';
        } catch (Throwable $e) {
            Log::error('Mail error: '.$e->getMessage(), [
                'to' => $to,
                'subject' => $subject,
            ]);

            return 'Mail error: '.$e->getMessage();
        }
    }
}

if (! function_exists('normalizeMailAddresses')) {
    function normalizeMailAddresses($addresses)
    {
        if (empty($addresses)) {
            return [];
        }

        if (is_string($addresses)) {
            $addresses = explode(',', $addresses);
        }

        return collect($addresses)
            ->map(function ($address) {
                return trim((string) $address);
            })
            ->filter(function ($address) {
                return filter_var($address, FILTER_VALIDATE_EMAIL);
            })
            ->values()
            ->all();
    }
}

if (! function_exists('normalizeMailAttachments')) {
    function normalizeMailAttachments($attachments)
    {
        if (empty($attachments)) {
            return [];
        }

        if (is_string($attachments)) {
            $attachments = [$attachments];
        }

        return collect($attachments)
            ->map(function ($attachment) {
                if (is_string($attachment)) {
                    return ['path' => $attachment, 'options' => []];
                }

                if (is_array($attachment) && ! empty($attachment['path'])) {
                    return [
                        'path' => $attachment['path'],
                        'options' => $attachment['options'] ?? [],
                    ];
                }

                return null;
            })
            ->filter(function ($attachment) {
                return ! empty($attachment['path']) && is_file($attachment['path']);
            })
            ->values()
            ->all();
    }
}

if (! function_exists('enc')) {
    function enc($value): string
    {
        $encryptedValue = Crypt::encryptString((string) $value);

        return rtrim(strtr($encryptedValue, '+/', '-_'), '=');
    }
}

if (! function_exists('dec')) {
    function dec(string $value): string
    {
        try {
            $encryptedValue = strtr($value, '-_', '+/');

            $padding = strlen($encryptedValue) % 4;

            if ($padding > 0) {
                $encryptedValue .= str_repeat('=', 4 - $padding);
            }

            return Crypt::decryptString($encryptedValue);
        } catch (DecryptException $e) {
            abort(404, 'Invalid encrypted reference.');
        }
    }
}

if (!function_exists('daysFromNow')) {
    function daysFromNow($lastDate)
    {
        if (empty($lastDate)) {
            return '-';
        }

        $currentDate = now()->startOfDay();
        $lastDate = \Carbon\Carbon::parse($lastDate)->startOfDay();

        if ($lastDate->greaterThan($currentDate)) {
            return '0 day';
        }

        $totalDays = (int) $lastDate->diffInDays($currentDate);

        if ($totalDays === 0) {
            return '0 day';
        }

        if ($totalDays === 1) {
            return '1 day';
        }

        if ($totalDays > 29) {
            return "<span style='display:inline-block; color:#fff; background-color:#d9534f; padding:2px 6px; border-radius:3px;'>{$totalDays} days</span>";
        }

        return $totalDays . ' days';
    }
}

if (!function_exists('daysFromNowCount')) 
{
    function daysFromNowCount($lastDate)
    {
        if (empty($lastDate)) {
            return 0;
        }

        $currentDate 	= now()->startOfDay();
        $lastDate 		= Carbon::parse($lastDate)->startOfDay();

        if ($lastDate->greaterThan($currentDate)) {
            return 0;
        }

        return (int) $lastDate->diffInDays($currentDate);         
    }
}
 
function getClientIp()
{
    foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
      if (array_key_exists($key, $_SERVER) === true) {
        foreach (explode(',', $_SERVER[$key]) as $ip) {
          $ip = trim($ip); // just to be safe
          if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
            return $ip;
          }
        }
      }
    }
    return request()->ip(); // it will return server ip when no client ip found
  }

if (! function_exists('ipProfile')) 
{    
	function ipProfile(string $ip, bool $fresh = false): ?array
	{
		$cacheKey = "ip_profile_{$ip}"; 
		if ($fresh) {
			Cache::forget($cacheKey);
		} 
		if (Cache::has($cacheKey)) {
			return Cache::get($cacheKey);
		}

		/* ─── 1.  GEOPLUGIN  ───────────────────────────── */
		$gp = json_decode(
			@file_get_contents("http://www.geoplugin.net/json.gp?ip={$ip}"),
			true
		);

		if ($gp && ($gp['geoplugin_status'] ?? 0) == 200) {
			return Cache::remember($cacheKey, now()->addHours(12), function () use ($ip, $gp) {
				return [
					'ip'      => $ip,
					'ip2'     => $gp['geoplugin_request']     ?? null,
					'city'    => $gp['geoplugin_city']        ?? null,
					'region'  => $gp['geoplugin_regionName']  ?? null,
					'country' => $gp['geoplugin_countryName'] ?? null,
					'postal'  => $gp['geoplugin_zip']         ?? null,
					'lat'     => $gp['geoplugin_latitude']    ?? null,
					'lon'     => $gp['geoplugin_longitude']   ?? null,
					'isp'     => null,
					'asn'     => null,
					'source'  => 'geoplugin',
				];
			});
		}

		/* ─── 2.  IP‑API  ─────────────────────────────── */
		$api = json_decode(
			@file_get_contents("http://ip-api.com/json/{$ip}?fields=status,message,country,countryCode,regionName,region,city,zip,lat,lon,isp,org,as,query"),
			true
		);

		if ($api && ($api['status'] ?? '') === 'success') {
			return Cache::remember($cacheKey, now()->addHours(12), function () use ($ip, $api) {
				return [
					'ip'      => $ip,
					'city'    => $api['city']       ?? null,
					'region'  => $api['regionName'] ?? null,
					'country' => $api['country']    ?? null,
					'postal'  => $api['zip']        ?? null,
					'lat'     => $api['lat']        ?? null,
					'lon'     => $api['lon']        ?? null,
					'isp'     => $api['isp']        ?? null,
					'asn'     => $api['as']         ?? null,
					'source'  => 'ip-api',
				];
			});
		}

		/* ─── 3.  IPINFO  ─────────────────────────────── */
		$info = json_decode(@file_get_contents("https://ipinfo.io/{$ip}/json"), true);

		if ($info && ! isset($info['error'])) {
			[$lat, $lon] = explode(',', $info['loc'] ?? ',');
			return Cache::remember($cacheKey, now()->addHours(12), function () use ($ip, $info, $lat, $lon) {
				return [
					'ip'      => $ip,
					'city'    => $info['city']    ?? null,
					'region'  => $info['region']  ?? null,
					'country' => $info['country'] ?? null,
					'postal'  => $info['postal']  ?? null,
					'lat'     => $lat ?: null,
					'lon'     => $lon ?: null,
					'isp'     => $info['org']     ?? null,
					'asn'     => $info['org']     ?? null,
					'source'  => 'ipinfo',
				];
			});
		}

		return null; // All lookups failed
	}

}
 

