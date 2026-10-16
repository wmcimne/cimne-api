const apiCall = document.getElementById('api-call');
const pageContent = document.querySelector('#main-content');



// Función genérica para llamadas a la API
const fetchAPI = async (url, params = {}) => {
    const query = new URLSearchParams(params).toString();
    //console.log(`Fetching API: ${url}?${query}`);
    const response = await fetch(`${url}?${query}`);
    return response.json();
};

// Funciones específicas para cada tipo de página
const getFilterData = async (params) => {
    return fetchAPI('https://metrics.cimne.com/ws/web/opts', params);
};

const loadProyectos = async (params) => {
    return fetchAPI('https://metrics.cimne.com/ws/web/projects', params);
};

const loadPublicaciones = async (params) => {
    // return fetchAPI('https://metrics.cimne.com/ws/web/publications', params);
    return fetchAPI('https://api.cimne.com/webcimne/publicaciones', params); /* NEW CODE */
};

const loadClusterOrGrupos = async (params) => {
    const [projects, publications, cluster, personal] = await Promise.all([
        fetchAPI('https://metrics.cimne.com/ws/web/projects', params),
        fetchAPI('https://metrics.cimne.com/ws/web/publications', params),
        fetchAPI('https://metrics.cimne.com/ws/web/cluster', params),
        fetchAPI('https://metrics.cimne.com/ws/web/personal', params)
    ]);
    return { projects, cluster, publications, personal };
};

const loadDirectorio = async (params) => {
    return fetchAPI('https://metrics.cimne.com/ws/web/personal', params);
};

const loadStaffProfile = async (params) => {
    return fetchAPI('https://ws.cimne.com/org/persona', params);
}




function loadPageData(pageType, params, isFilter = false) {
    switch (pageType) {
        case 'projects':
            return loadProyectos(params);
        case 'publications':
            const today = new Date().toISOString().split("T")[0];
            //params['date_to'] = params['date_to'] || today; /* NEW CODE */
            return loadPublicaciones(params);
        case 'onate':
             return loadPublicaciones(params); /* NEW CODE */
        case 'cluster':
        case 'rtd':
            //console.log('loadPageData - cluster:', pageType);
            return isFilter ? loadPublicaciones(params) : loadClusterOrGrupos(params);
        case 'directory':
            return loadDirectorio(params);
        case 'staff':
            //console.log('loadPageData - staff:', params);
            return loadStaffProfile(params);
        default:
            throw new Error('Tipo de página no soportado');
    }
}

const getPageType = () => {

    if (!apiCall) {
        //console.log('apiCall element not found');
        return { pageType: null, params: {} };
    }

    let pageType = '';
    let clusterCode = null;
    let rtdCode = null;
    let params = {}; 

    if (apiCall.classList.contains('projects-page')) {
        pageType = 'projects';
        const querystring = window.location.search;
        const project_id = new URLSearchParams(querystring).get('id');
        //console.log(project_id);
        if (project_id) {
            //params['id'] = params.get('id'); // Añade el ID del personal a los parámetros
            params['id'] = project_id; // Añade el ID del proyecto a los parámetros
        } 

    } else if (apiCall.classList.contains('publications-page')) {
        pageType = 'publications';
    } else if (apiCall.classList.contains('onate-page')) {
        pageType = 'onate';
        params['auth_scopus'] = '55147932200'; // Añade el autor 'onate' a los parámetros para filtrar publicaciones
    } else if (apiCall.classList.contains('cluster-page')) {
        pageType = 'cluster';
        clusterCode = apiCall.getAttribute('cluster-code');
        params['cluster'] = clusterCode; // Añade el código del cluster a los parámetros
        
    } else if (apiCall.classList.contains('rtd-page')) {
        pageType = 'rtd';
        rtdCode = apiCall.getAttribute('rtd-code');
        params['rtd'] = rtdCode; // Añade el código del RTD a los parámetros
    } else if (apiCall.classList.contains('directory-page')) {
        pageType = 'directory';
    } else if (apiCall.classList.contains('staff-page')) {
        pageType = 'staff';
        const querystring = window.location.search;
        params = new URLSearchParams(querystring);
        //console.log(params);
        if (params.get('id')) {
            params['id'] = params.get('id'); // Añade el ID del personal a los parámetros
        } else {
            const container = document.querySelector('#staff-profile-container');
            container.innerHTML = `<p class="no-results">${wsStrings[locale].no_staff}</p>`;
            //console.error('ID del personal no encontrado en la URL');
            return { pageType: '', params: {} };
        }
        
    }
    return { pageType, params };
};


document.addEventListener('DOMContentLoaded', async () => {

    
if(apiCall) {
        const { pageType, params } = getPageType();

        if (pageType) {

            let isInProjectsCardPage = apiCall.classList.contains('project-card');
            //console.log(`Page type: ${pageType}, Params: ${JSON.stringify(params)}, Is in projects card page: ${isInProjectsCardPage}`);

            if (pageType == 'projects' && isInProjectsCardPage && params.hasOwnProperty('id')) {

                try {

                    const wsURL = `https://metrics.cimne.com/ws/web/project?id=${params.id}`;
                    const data = await fetchAPI(wsURL);
                    
                    generateProjectCardHTML(params.id, data);

                } catch (error) {
                    document.querySelector('#projects-container').innerHTML = `<p class="no-results">Error loading project with ID ${params.id}. Please try again later.</p><p class="no-results"><a href="/">Go to homepage.</a></p>`;
                    //console.log(`Error al cargar el proyecto con ID ${params.id}:`, error);
                } 
                
            } else  if (pageType == 'projects' && isInProjectsCardPage && !params.hasOwnProperty('id')) {
                document.querySelector('#projects-container').innerHTML = `<p class="no-results">A project ID is required. Please try again later.</p><p class="no-results"><a href="/">Go to homepage.</a></p>`;
            } else {
                
                try {

                    const data = await loadPageData(pageType, params);
                    const filterData = await getFilterData(params);

                    //console.log("Page type:", pageType, "Data:", data, "Filter Data:", filterData, "Params:", params);

                    renderPageData(pageType, data, filterData);

                } catch (error) {

                    // Error al cargar los datos de la página
                }
            }
        }
    }
});

document.addEventListener('click', async (event) => {
    if(apiCall) {
        if (event.target.classList.contains('filter-data')) {

            let formId = event.target.closest('form').id;
            const { pageType, params } = getPageType();
            let filterParams = { ...params, ...getFilterParams(formId) };

            //console.log(`Filter data clicked, formId: ${formId}, pageType: ${pageType}, filterParams: ${JSON.stringify(filterParams)}`);

            if (pageType) {
                try {
                    const data = await loadPageData(pageType, filterParams, true);
                    //console.log(data);
                    const filterData = await getFilterData(params);

                    renderFilterData(pageType, data, filterData);
                    
                } catch (error) {
                    // Error al cargar los datos de la página
                }
            }

        } else if (event.target.classList.contains('refresh-filter')) {

            const formToReset = document.querySelectorAll('#search-bar form'); // sustituido en proyectos por el id "cimne-projects-app"
            const { pageType, params } = getPageType();

            formToReset.forEach(form => {
                form.reset();
            });

            if (pageType) {

                try {
                    const data = await loadPageData(pageType, params);
                    const filterData = await getFilterData(params);

                    renderPageData(pageType, data, filterData);

                } catch (error) {

                    // Error al cargar los datos de la página
                }
            }
        }
    }
});

const renderPageData = (pageType, data, filterData) => {

    //console.log("Rendering page data. Page type:", pageType, "Data:", data, "Filter Data:", filterData);

    const {cluster, projects, publications, personal} = data;

    //console.log("personal:", personal);

    switch (pageType) {

        case 'projects':

            let filterParams = {};
            const { projects: projectsOptsPage } = filterData;
            generateProjectsHTML(data, projectsOptsPage, filterParams);
            generateProjectsFilter(filterData);

            break;

        case 'publications':
            //console.log("Render publications data:", data);
            generatePublicationsHTML(data);
            generatePublicationsFilter();

            break;

        case 'onate':
            //console.log("Render ONATE publications data:", data);
            generatePublicationsHTML(data);

            break;
    
        case 'cluster':
        case 'rtd':
            
            const isInnovationUnit = document.URL.includes(wsStrings[locale].bee_group_slug) || document.URL.includes(wsStrings[locale].cenit_slug);
            const { personal: personalOptsCluster } = filterData;
            const { projects: projectsOptsCluster } = filterData;

            if (!isInnovationUnit && pageType === 'cluster') {
                generateGroupsHTML(cluster);
                generateStaffHTML(cluster, personal, personalOptsCluster );
            } else if (!isInnovationUnit && pageType === 'rtd') {
                generateStaffHTML(cluster, personal, personalOptsCluster );
            }
            
            generateProjectsHTML(projects, projectsOptsCluster, {});
            generatePublicationsHTML(publications);
            generatePublicationsFilter(cluster);

            break;
        
        case 'directory':

            generateDirectoryHTML(data);
            generateDirectoryFilter(filterData);

            break;

        case 'staff':

            //console.log("Render staff profile:", data);

            // Si data es un arreglo, usar el primer elemento
            if (Array.isArray(data) && data.length > 0) {
                generateStaffProfileHTML(data[0]);
            } else {
                generateStaffProfileHTML(data);
            }
            break;

        default:
            // Tipo de página no soportado para renderizado
    }
};

const renderFilterData = (pageType, data, filterData) => {

    switch (pageType) {

        case 'projects':

            let filterParams = {};
            const { projects: projectsOptsFilter } = filterData;
            generateProjectsHTML(data, projectsOptsFilter, filterParams);

            break;

        case 'publications':
        case 'onate':
        case 'cluster':
        case 'rtd':

            generatePublicationsHTML(data);

            break;

        case 'directory':

            generateDirectoryHTML(data);

            break;

        default:
            // Tipo de página no soportado para renderizado
    }
};

const getFilterParams = (formId) => {

    //console.log(`getFilterParams: ${formId}`);

    const selector = `#${formId} .form-group input, #${formId} .form-group select`;
    //console.log(`Selector: ${selector}`);

    const item = document.querySelectorAll(selector);
    const idTovalue = {
        'group': 'category',
        'research-cluster': 'cluster',
        'research-rtd': 'rtd',
        'principal-investor': 'ip_id',
        'publication-type': 'subtype',
        'start-date': 'date_from',
        'end-date': 'date_to'
    }
    const formValues = {};

    item.forEach(element => {
        if (element.value) {
            if (idTovalue[element.id] === 'ip_id') {
                // Buscar el option seleccionado en el datalist
                const datalistId = element.getAttribute('list');
                if (datalistId) {
                    const datalist = document.getElementById(datalistId);
                    if (datalist) {
                        const option = Array.from(datalist.options).find(opt => opt.value === element.value);
                        if (option) {
                            formValues['ip_id'] = option.classList[0] || '';
                        } else {
                            formValues['ip_id'] = '';
                        }
                    } else {
                        formValues['ip_id'] = '';
                    }
                } else {
                    formValues['ip_id'] = '';
                }
            } else {
                formValues[idTovalue[element.id]] = element.value;
            }
        }
    });

    //console.log(`Form values: ${JSON.stringify(formValues)}`);
    return formValues;
}



// Eventos de clic síncronos en el contenido principal
pageContent.addEventListener('click', event => {
    if (event.target.classList.contains('send-email')) {
        openEmailClient(getMailCimne(event.target.id));
    }
    if (event.target.classList.contains('download-ics')) {
        //console.log('Generating vCard...');
        generarVCard();
    }
	if (event.target.classList.contains('copy-email-to-clipboard')) {
        const textToCopy = getMailCimne(event.target.id) || event.target.textContent;
        navigator.clipboard.writeText(textToCopy).then(() => {

            const originalText = event.target.textContent;
            event.target.textContent = wsStrings[locale].email_copied;
            setTimeout(() => { 
                event.target.textContent = originalText; 
            }, 2000);

        }).catch(err => {
            console.error('Error copying to clipboard:', err);
        });
    }
});

// Eventos de clic asíncronos en todo el documento
document.addEventListener('click', async (event) => {
    if (event.target.classList.contains('open-project')) {
        try {
            const wsURL = `https://metrics.cimne.com/ws/web/project?id=${event.target.id}`;
            //console.log(wsURL);
            const data = await fetchAPI(wsURL);
            //console.log(data);
            generateProjectCardHTML(event.target.id, data);
        } catch (error) {
            // manejo del error
        } finally {
            //event.target.remove();
        }
    }
    
    if (event.target.classList.contains('share-project')) {
        const projectURL = `https://cimne.com/research/projects/project-card/?id=${event.target.id}`;
        try {
            await navigator.clipboard.writeText(projectURL);
            //console.log('Contenido copiado al portapapeles');
            /* Resuelto - texto copiado al portapapeles con éxito */
        } catch (err) {
            console.error('Error al copiar: ', err);
            /* Rechazado - fallo al copiar el texto al portapapeles */
        }
    }

    // Paginación de publicaciones: botones generados por generatePublicationsHTML
    if (event.target.classList.contains('pub-page-btn')) {
        //console.log('Publication page button clicked:', event.target.dataset.page);
        event.preventDefault();
        const page = parseInt(event.target.dataset.page, 10);
        if (isNaN(page)) return;
        //console.log(`Loading page ${page} of publications...`);

        // comprobar si hay filtros activos en la página para mantenerlos al cambiar de página
        let filterParams = {};
        const formInPage = document.querySelectorAll('#publications-form');
        if (formInPage.length > 0) {
            filterParams = {...getFilterParams('publications-form')};
            //console.log(`Current filter params: ${JSON.stringify(filterParams)}`);
        }

        // Recuperar metadatos almacenados en el contenedor de paginación
        const paginationContainer = document.getElementById('publications-pagination');
        const limit = paginationContainer && paginationContainer.dataset.limit ? parseInt(paginationContainer.dataset.limit, 10) : null;

        const { pageType, params } = getPageType();
        if (pageType !== 'publications' && pageType !== 'onate') return;
        //console.log(`Current params: ${JSON.stringify(params)}, limit: ${limit}`);

        const queryParams = { ...params, ...filterParams };
        if (limit) queryParams.limit = limit;
        // Calcular offset según página y limit (si no existe limit, usar page como offset)
        queryParams.offset = limit ? (page - 1) * limit : (page - 1);

        //console.log(`Query params for API call: ${JSON.stringify(queryParams)}`);

        try {
            const data = await loadPageData(pageType, queryParams);
            const filterData = await getFilterData(params);
            //console.log(`Filter data for page ${page}: ${JSON.stringify(filterData)}`);

            // Renderizar los datos recibidos
            renderFilterData(pageType, data, filterData); // Pasar el objeto filterData para la paginación

            // Llevar el foco/scroll al contenedor de publicaciones
            const pubContainer = document.querySelector('#publications-container');
            if (pubContainer) pubContainer.scrollIntoView({ behavior: 'smooth' });

            //Actualizar la URL con el número de página (no recarga)
            // try {
            //     const url = new URL(window.location.href);
            //     url.searchParams.set('page', String(page));
            //     console.log(`Updating URL to: ${url.toString()}`);
            //     window.history.replaceState({}, '', url.toString());
            // } catch (e) {
            //     // Ignorar si fallan APIs de history
            //      console.error('Error actualizando URL:', error.message);
            // }

        } catch (error) {
            console.error('Error loading page:', error);
        }

        return;
    }

    if (event.target.classList.contains('download-ics')) {
        try {
            //const wsURL = `https://metrics.cimne.com/ws/web/persona?id=1436 `;
            const wsURL = `https://metrics.cimne.com/ws/web/persona?id=${event.target.id}`;
            //console.log(wsURL);
            const data = await fetchAPI(wsURL);
            //console.log(data);
            generateDirectoryProfileHTML(data);
        } catch (error) {
            // manejo del error
        } finally {
            //event.target.remove();
        }
    }


    if (event.target.classList.contains('project-back')) {
        const { pageType, params } = getPageType();

        //console.log(`Back button clicked, pageType: ${pageType}, params: ${JSON.stringify(params)}`);

       
        try {
            document.querySelector('#ongoing-projects-item').click();
            const data = await loadPageData(pageType, params);
            const filterData = await getFilterData(params);
            //console.log(data);
            // Aquí puedes llamar a la función que renderiza los datos en la página
            renderPageData(pageType, data, filterData);
        } catch (error) {
            // Error al cargar los datos de la página
        }
       
    }

    if (event.target.classList.contains('back')) {
        
        //console.log(`Staff Back button clicked`);
            window.history.go(-1);
    }
});