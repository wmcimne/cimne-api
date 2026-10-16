// https://sheets.googleapis.com/v4/spreadsheets/{spreadsheetId}/values/{range}?key={yourAPIKey}

const gSheetCall = document.getElementById('gsheets-call');

const getSheetType = () => {

    const apikey= 'AIzaSyCM-3u1_cJCOSTvbXIa6UyqGgUj0euNdgg';
    let sheetId= null, tabName= null, range= null;

    if (!gSheetCall) {
        console.log('gSheetCall element not found');
        return {
            sheetId, 
            tabName, 
            apikey, 
            range
        }
    }

    const spreadsheetId = {
        "innovation-unit-in-transport-cenit": "1sSgLMlmaWTPSyaVZxSh2OETP--O9NevswQgc1RwWxyQ",
        "building-energy-and-environment-beegroup": "10pMxXzD0-c_uuiIy788Tbm49imFE4x5YatJi_gYdjO4",
        "pre-post-and-digital-technologies": "",
        "phd-theses": "1au3CsOYkxS2yLhYX-YTsc7XCGNzCBxsvGO5xO1tyxuM",
        "awards": "1au3CsOYkxS2yLhYX-YTsc7XCGNzCBxsvGO5xO1tyxuM",
        "oko-card": "1dElHzsKJ-0FD6ut_2oxKJOYLcUca3lWQ63--H2mlCxM"
    };

    const spreadsheetRange = {
        "innovation-unit-in-transport-cenit": "!A2:I11",
        "building-energy-and-environment-beegroup": "!A2:I11",
        "pre-post-and-digital-technologies": "!A2:I11",
        "phd-theses": "!A2:G897",
        "awards": "!A2:D55",
        "oko-card": "!A2:E50"
    }

    sheetId = spreadsheetId[gSheetCall.getAttribute('sheet-code')] || null;
    tabName = gSheetCall.getAttribute('rl-code');
    range = spreadsheetRange[gSheetCall.getAttribute('sheet-code')] || null;
    
    return {
        sheetId,
        tabName,
        apikey,
        range
    }
};



const convertToKeyValue = (data) => {

    let urlPathname = window.location.pathname;
    //console.log(urlPathname);
    let keys = [];
  
    if (urlPathname.includes(wsStrings[locale].cenit_slug)) {
		//console.log(urlPathname + "Cenit");
        keys = ["name", "description", "client", "period", "partners", "abstract", "website", "image", "files"];
    } else if (urlPathname.includes( wsStrings[locale].bee_group_slug )) {
        keys = ["name", "description", "client", "period", "partners", "abstract", "website", "image", "files"];
    } else if (urlPathname.includes("pre-post-and-digital-technologies")) {
        keys = ["name", "description", "client", "period", "partners", "abstract", "website", "image", "files"];   
    } else if (urlPathname.includes(wsStrings[locale].phd_thesis)) {
        keys = ["title", "readingDate", "author", "directors", "programme", "qualification", "url"];
    } else if (urlPathname.includes(wsStrings[locale].awards) || urlPathname.includes("eonate")) {
        keys = ["name", "position", "imgUrl", "awards"];
    } else if (urlPathname.includes('oko-card')) {
        keys = ["id", "email", "link", "linkedin", "x"];
    } else {
        console.error("No matching URL path for converting data to key-value pairs.");
        return [];
    }
  
    return data.values.map(row => {
        return Object.fromEntries(keys.map((key, index) => [key, row[index]]));
    });
}
  
 

window.addEventListener('load', async (event) => {

    //console.log("Cimne API request: " + event);  
    const urlPathname = window.location.pathname;
    //event.target.textContent = 'Loading...';
    const querystring = window.location.search;
        
    try {

        if (gSheetCall) {
            const wsURL = `https://sheets.googleapis.com/v4/spreadsheets/${getSheetType().sheetId}/values/${getSheetType().tabName}${getSheetType().range}?key=${getSheetType().apikey}`;
            //console.log(wsURL);
            const data = await cimneAPIrequest(wsURL);
            const convertedData = convertToKeyValue(data);
            //console.log(convertedData);

            if (gSheetCall.getAttribute('sheet-code') == "innovation-unit-in-transport-cenit"  || gSheetCall.getAttribute('sheet-code') == "building-energy-and-environment-beegroup" ) {
                //console.log("Generating contracts HTML...");
             generateContractsHTML(convertedData);
            } else if (gSheetCall.getAttribute('rl-code') == "phd-theses") {
                //console.log("Generating theses HTML...");
                generateThesesHTML(convertedData);
            } else if (gSheetCall.getAttribute('rl-code') == "awards") {
                //console.log("Generating awards HTML...");
                generateAwardsHTML(convertedData, gSheetCall.getAttribute('onate'));
            }else if (gSheetCall.getAttribute('rl-code') == "oko-card") {
                //console.log("Generating OKO cards HTML social media...");
                const oko_id = new URLSearchParams(querystring).get('id');
                generateOKOCardsSocialHTML(oko_id, convertedData);
            }else{
                //console.log("No matching sheet code or rl code for generating HTML.");
            }    
        } else {
            console.log('gSheetCall element not found');
        }

    } catch (error) {
        // manejo del error
        console.log(error);
    } finally {
        //event.target.remove();
    }
});

