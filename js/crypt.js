// async function encrypt(p,s){const u=new TextEncoder().encode(p),w=new TextEncoder().encode(s),h=await crypto.subtle.digest("SHA-256",w),i=crypto.getRandomValues(new Uint8Array(16)),k=await crypto.subtle.importKey("raw",h,{name:"AES-CBC",iv:i},!1,["encrypt"]),c=new Uint8Array(await crypto.subtle.encrypt({name:"AES-CBC",iv:i},k,u)),x=Array.from(i).map(j=>("00"+j.toString(16)).slice(-2)).join("");return x+btoa(String.fromCharCode(...c))}
// async function decrypt(c,s){const x=c.slice(0,32),t=c.slice(32),i=new Uint8Array(x.match(/.{1,2}/g).map(b=>parseInt(b,16))),r=atob(t),b=new Uint8Array(r.split("").map(k=>k.charCodeAt(0))),h=await crypto.subtle.digest("SHA-256",new TextEncoder().encode(s)),k=await crypto.subtle.importKey("raw",h,{name:"AES-CBC",iv:i},!1,["decrypt"]),l=new TextDecoder().decode(await crypto.subtle.decrypt({name:"AES-CBC",iv:i},k,b));return l}

function getMailCimne(base64String) {
    // Elimina el último carácter de la cadena
    const trimmedString = base64String.slice(0, -1);
    
    // Decodifica la cadena base64
    try {
        const decodedString = atob(trimmedString);
        return decodedString;
    } catch (error) {
        console.error("Error al decodificar la cadena base64:", error);
        return null;
    }
}

// Example usage
// openEmailClient("example@domain.com");
function openEmailClient(emailAddress) {
    if (emailAddress) {
        window.location.href = `mailto:${emailAddress}`;
    } else {
        console.error("Invalid email address.");
    }
}



