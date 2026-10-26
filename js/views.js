/*-----------------------------------------------------------------
   Sanitización de datos para evitar inyección de HTML

------------------------------------------------------------------*/
// function escapeHtml(text) {
//   const map = {
//     '&': '&amp;',
//     '<': '&lt;',
//     '>': '&gt;',
//     '"': '&quot;',
//     "'": '&#x27;',
//     '/': '&#x2F;'
//   };
//   return text.replace(/[&<>"'/]/g, function(m) { return map[m]; });
// }
/**
 * Sanitiza una cadena para evitar inyección de HTML.
 * @param {string} str - La cadena a sanitizar.
 * @returns {string} - La cadena sanitizada.
 */
// function sanitize(str) {
//     if (typeof str !== 'string') return '';
//     return str
//         .replace(/&/g, '&amp;')
//         .replace(/</g, '&lt;')
//         .replace(/>/g, '&gt;')
//         .replace(/"/g, '&quot;')
//         .replace(/'/g, '&#39;');
// }


/*-----------------------------------------------------------------
    Llamada a la API
------------------------------------------------------------------*/
const cimneAPIrequest = async (wsURL) => {

    try {
        //console.log(wsURL);
        const resp = await fetch(wsURL);
        //console.log(resp);
        const json = await resp.json();
        //console.log(json);
        return json;

    } catch (error) {
        // manejo del error
        //console.log(error);
    }
}

/*-----------------------------------------------------------------
    Obtiene el nombre de la página actual
------------------------------------------------------------------*/

const getCurrentPage = () => {
        const path = window.location.pathname;
        // Elimina la última barra si existe
        const cleanPath = path.endsWith('/') ? path.slice(0, -1) : path;
        // Obtiene el último segmento de la ruta
        const segments = cleanPath.split('/');
        return segments[segments.length - 1] || 'home';
};

/*-----------------------------------------------------------------
    Lang para traducciones
------------------------------------------------------------------*/

const getLanguage = () => {
    return document.documentElement.lang.substring(0, 2);
}
const locale = getLanguage();


/*-----------------------------------------------------------------
    Format dates to DD-MM-YYYY
------------------------------------------------------------------*/

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    }).replace(/\//g, '-');
};

/*-----------------------------------------------------------------
    Genera el HTML de la página de Clusters y Grupos
------------------------------------------------------------------*/

const getCodeWithURLWebsite = (webURL) => {
    //console.log(webURL);

    let desktopCode = `<div class="flecha-container flecha-container list-website list-website-desktop">
                            <span class="cuerpo-flecha"> </span>
                            <span class="punta-flecha"> </span>
                            <span class="text-flecha"><a href="${webURL}" target="_blank" rel="noopener">WEBSITE</a></span>
                        </div>`;
    let mobileCode = `<div class="list-website list-website-mobile"><span><del>       </del><a href="${webURL}" target="_blank" rel="noopener"> WEBSITE</a></span></div>`;

    return { desktopCode, mobileCode };

}

const generateGroupsHTML = ({ rtd_groups }) => {

    const container = document.querySelector('#cluster-groups');

    if (!container) return;

    const groupHTML = rtd_groups.map(
        ({ name, leader }) => {
            //console.log(`Name: ${name} Leader: ${leader}`);
            //const sanitizedName = name.replace(/[()&]/g, '').replace(/ /g, '-').toLowerCase().normalize("NFD").replace(/[u0300-u036f]/g, "");
            const sanitizedName = name.replace(/[()&]/g, '').replace(/ /g, '-').toLowerCase();
            //console.log(`Sanitized Name: ${sanitizedName}`);
            const leaderName = leader ? `${leader.first_name} ${leader.last_name} ${leader.last_name2}` : 'No leader assigned';
            return `<li class="item-cluster-group"><a href="${document.URL}${sanitizedName}">${name}</a><br><span class="item-cluster-group-subtitle">${leaderName}</span></li>`;
        }
    ).join('');

    //console.log(groupHTML);

    document.querySelector('#cluster-groups').innerHTML = groupHTML;
}


const generateStaffHTML = ({ rtd_groups }, { personal }, { category }) => {

    ////console.log( 'rtd_groups: ' + rtd_groups.length);
    //console.log( 'personal: ' + personal);
    //console.log( 'category: ' + category);

    const container = document.querySelector('#staff-container');

    let html = '';
    let leadersId = [];
	let emailString = '';

    //console.log('Grupos de investigación: ' + rtd_groups.length);

    //console.log('rtd_groups: ' + !!rtd_groups);

    if(rtd_groups) {
        html += `<h5 class="staff-heading">${wsStrings[locale].leaders}</h5><div class="staff-block">`;

        rtd_groups.forEach((group, index) => {
            const { leader } = group;
            //console.log("Leader: " + leader.personal_id);
            leadersId[index] = leader.personal_id;
        })

        leadersId.forEach((id) => {
            //console.log("Leader ID: " + id);
            let staffBlock = '';
            personal.forEach(({ first_name, last_name, last_name2, email, role, category: personCategory, phone, person_id }) => {
                //console.log("Personal ID: " + person_id);
                //emailString = email ? getMailCimne(email) : 'No Email';
				if (id === person_id) {
                    html +=        
                    `<div class="staff-item">
                        <p id="${person_id}" class="staff-name open-profile"><a href="/about/directory/staff-profile/?id=${person_id}">${first_name} ${last_name} ${last_name2}</a></p>
                        <p class="staff-phone">${phone ? phone : 'No phone'}</p>

                        <p id="${email}" class="staff-email copy-email-to-clipboard">${wsStrings[locale].copy_email}</p>
                    </div>`;
                }
                
            }); 
            
        });
        html += '</div>';
    }

    const categoryOrder = [
        "Senior Distinguished Researcher",
        "Full Research Professor",
        "Associate Research Professor",
        "Senior Innovation Leader",
        "Assistant Research Professor",
        "Innovation Leader",
        "Post Doc",
        "Innovation Developer",
        "PHD Student",
        "Master Student",
        "Innovation Trainee",
        "Research Engineer",
        "Staff Scientist",
        "Undergraduate Students",
        "Visiting Scientist"
    ];

    const orderedCategories = category.slice().sort((a, b) => {
        const indexA = categoryOrder.indexOf(a);
        const indexB = categoryOrder.indexOf(b);
        return (indexA === -1 ? categoryOrder.length : indexA) - (indexB === -1 ? categoryOrder.length : indexB);
    });

    orderedCategories.forEach((cat) => {
        let staffBlock = '';
        personal.forEach(({ first_name, last_name, last_name2, email, role, category: personCategory, phone, person_id }) => {
            if (cat === personCategory && !leadersId.includes(person_id)) {
                staffBlock += 
                    `<div class="staff-item">
                        <p id="${person_id}" class="staff-name open-profile"><a href="/about/directory/staff-profile/?id=${person_id}">${first_name} ${last_name} ${last_name2}</a></p>
                        <p class="staff-phone">${phone ? phone : 'No phone'}</p>

                        <p id="${email}" class="staff-email copy-email-to-clipboard">${wsStrings[locale].copy_email}</p>
                    </div>`;
            }
        });

        if (staffBlock) {
            html += `<h5 class="staff-heading">${cat}</h5><div class="staff-block">${staffBlock}</div>`;
        } 
    });

    container.innerHTML = html;
}

const generateProjectsHTML = ({ projects }, { category }) => {

    const container = document.querySelector('#projects-container');

    //console.log('Projects HTML: ' + projects.length);

    if (projects.length === 0) {
        container.innerHTML = `<p class="no-projects">${wsStrings[locale].no_projects}</p>`;
        return;
    }

    let html = '';

    category.forEach((categoryItem) => {

        let categoryClass = categoryItem.replace(' ', '-').toLowerCase().normalize("NFD").replace(/[u0300-u036f]/g, "");
        html += `<dl class="description-list projects-list ${categoryClass}-projects"><h5 class="title ${categoryClass}-projects-title">${categoryItem}</h5>`;

        projects.forEach((project) => {
            const { proj_id, acronym, title, category, start, end, finished, web, financing_entity, program } = project;
			
			const formattedStartDate = formatDate(start);
            const formattedEndDate = formatDate(end);

            if (category === categoryItem) {

                let isFinished = finished ? 'hide-element finished' : 'ongoing';
                let hasWebsiteHTMLCode = web ? getCodeWithURLWebsite(web) : { desktopCode: '<span></span>', mobileCode: '<span></span>' };

                html += `<div class="list-block ${isFinished}-project">
                            <div class="list-title">
                                <span id="${proj_id}" class="open-project">
                                    ${acronym}
                                </span>
                                ${hasWebsiteHTMLCode.desktopCode}
                            </div>
                            <div class="list-title">
                                <span id="${proj_id}" class="open-project">
                                    ${title}
                                </span>
                            </div>
                            <div class="list-item">
                                <dt>${wsStrings[locale].start}:</dt>
                                <dd>${formattedStartDate}</dd>
                            </div>
                            <div class="list-item">
                                <dt>${wsStrings[locale].end}:</dt>
                                <dd>${formattedEndDate}</dd>
                            </div>
                            <div class="list-item">
                                <dt>${wsStrings[locale].funding_body}:</dt>
                                <dd>${financing_entity}</dd>
                            </div>
                            <div class="list-item">
                                <dt>${wsStrings[locale].program}:</dt>
                                <dd>${program}</dd>
                            </div>
                            ${hasWebsiteHTMLCode.mobileCode}
                        </div><!–nextpage–>`;
            }
        });

        html += '</dl>';

    });
    container.innerHTML = html;

}

/*-----------------------------------------------------------------
    Genera el HTML del bloque de la ficha de proyecto
------------------------------------------------------------------*/

const generateProjectCardHTML = (id, data) => {

    const { Acronimo, Titulo, Foto, LogoProyecto, InvestPpal, EmailIP, Abstract, Web, Referencia, FechaInicio, FechaFin, Coordinator, Programa, SubPrograma, Call, Categoria, EntidadFinanciadora, TotalGrant, PiePaginaWeb } = data;
	
	const formattedStartDate = formatDate(FechaInicio);
    const formattedEndDate = formatDate(FechaFin);
    const logoKeys = Object.keys(data).filter(key => key.startsWith('Logo') && key !== 'LogoProyecto' && key !== 'LogoPlan' && data[key] != null);
    const logoArray = logoKeys.map(key => data[key]);
    const container = document.querySelector('#projects-container');
    const profileImg = Foto ? `data:image/jpg;base64,${Foto}` : '/wp-content/uploads/2024/12/Hidden_profile_pic.png';
    const websiteBlock = Web ? getCodeWithURLWebsite(Web) : { desktopCode: '<span></span>', mobileCode: '<span></span>' };
    const proyectLogo = LogoProyecto ? `<div class="right logos"><img class="proyect-pic" src= "data:image/jpg;base64,${LogoProyecto}" alt="proyect logo"></div>` : '';


    let html = `
    <div id="project-ficha"> 
        <div class="project-ficha-back">
			<div><span id="${id}" class="share-project">Copy project URL</span></span></div>
            <div><span class="project-back"">${wsStrings[locale].back}</span></div>
        </div>
        <div class="project-ficha-heading">
            <div class="left">
                <h5 class="acronim">${Acronimo}</h5>
                <p class="title">${Titulo}</p>
            </div>

                ${proyectLogo}
  
        </div>
        <div class="project-ficha-sub-heading">
            <div class="logos-others">`;

    logoArray.forEach((logo, index) => {
		//console.log(EntidadFinanciadora.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, ""));
        if(EntidadFinanciadora.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "") == 'accio') {
            html += `<a href="https://www.accio.gencat.cat/" target="_blank"><img class="finac-pic accio" src="data:image/jpg;base64,${logo}" alt="finacial body logos"></a>`;
        } else {
            html += `<img class="finac-pic" src="data:image/jpg;base64,${logo}" alt="finacial body logos">`;
        }
    });
    html += `</div>
            <div class="web-footer">
                <p class="text">${PiePaginaWeb || ''}</p>
            </div>
        </div>
        <div class="project-ficha-body">
        <div class="left">
                <div class="profile">
                    <div class="profile-pic" style="background-image: url(${profileImg})">
                                                
                    </div>
                    <div class="profile-info">
                        <p class="category">${wsStrings[locale].principal_investigator}</p>
                        <p class="name">${InvestPpal}</p>
                        <p class="email">${EmailIP}<p>
                    </div>
                </div>
                <div class="abstract">
                    <p class="text">${Abstract}</p>                   
                </div>
            </div> 
        
            <div class="right">
           <!-- <details id="consortium-members" name="acordeon">
                    <summary>${wsStrings[locale].consortium_members}</summary>
                    <ul>-->`;
                    
        // partners.forEach((partner) => {
        //     html += `<li>${partner}</li>`;
        // });

        html += `
        <!--</ul>
        </details>-->
        <details id="more-information" name="acordeon">
            <summary>${wsStrings[locale].more_information}</summary>
            <dl>
                <div class="list-item">
                    <dt>${wsStrings[locale].reference}:</dt>
                    <dd>${Referencia || ''}</dd>
                </div>
                <div class="list-item">
                    <dt>${wsStrings[locale].start}:</dt>
                    <dd>${formattedStartDate || ''}</dd>
                </div>
                <div class="list-item">
                    <dt>${wsStrings[locale].end}:</dt>
                    <dd>${formattedEndDate}</dd>
                </div>
                <div class="list-item">
                    <dt>${wsStrings[locale].coordinator}:</dt>
                    <dd>${Coordinator || ''}</dd>
                </div>
                <div class="list-item">
                    <dt>${wsStrings[locale].program}:</dt>
                    <dd>${Programa || ''}</dd>
                </div>
                <div class="list-item">
                    <dt>${wsStrings[locale].call}:</dt>
                    <dd>${Call || ''}</dd>
                </div>
                <div class="list-item">
                    <dt>${wsStrings[locale].subprogramme}:</dt>
                    <dd>${SubPrograma || ''}</dd>
                </div>
                <div class="list-item">
                    <dt>${wsStrings[locale].category}:</dt>
                    <dd>${Categoria || ''}</dd>
                </div>
                <div class="list-item">
                    <dt>${wsStrings[locale].funding_body}:</dt>
                    <dd>${EntidadFinanciadora || ''}</dd>
                </div>
                <div class="list-item">
                    <dt>${wsStrings[locale].grant}:</dt>
                    <dd>${TotalGrant || ''}</dd>
                </div>
            </dl>
        </details>
            </div>
        </div>
        <div class="project-ficha-extra">
            ${websiteBlock.desktopCode}${websiteBlock.mobileCode}
        </div>
    </div>`;

    container.innerHTML = '<p>Hola Mundos</p>'; // Limpiar el contenedor antes de añadir el nuevo HTML
    container.innerHTML = html;
}

/*-----------------------------------------------------------------
    Genera el HTML del filtro de la página de Proyectos
------------------------------------------------------------------*/

const generateProjectsFilter = ({ projects }) => {

    const {category, ips} = projects;

    const clusterOpts = ['RC1', 'RC2', 'RC3', 'RC4', 'RC5', 'RC6', 'RC7', 'RC8', 'RC9', 'IU1', 'IU2', 'IU3'];

    // const opts_cluster = {
    //     all: [
    //         ['RC1', 'Geomechanics And Hydrogeology'],
    //         ['RC2', 'Machine Learning And Models In Hydro Environmental Engineering'],
    //         ['RC3', 'Aeronautical Marine Automotive And Energy Engineering'],
    //         ['RC4', 'Solid And Fluid Simulation For Industrial Processes'],
    //         ['RC5', 'Computational Mechanics In Medical Engineering And Living Matter'],
    //         ['RC6', 'Structural And Particle Mechanics'],
    //         ['RC7', 'Mechanics Of Advanced Materials And Metamaterials'],
    //         ['RC8', 'Credible High Fidelity And Data Driven Models'],
    //         ['RC9', 'Large Scale Multiphysics Computations'],
    //         ['IU1', 'Centre For Innovation In Transport Cenit'],
    //         ['IU2', 'Building Energy And Environment Beegroup'],
    //         ['IU3', 'Pre Post And Digital Technologies']
    //     ],
    //     filtered: ['RC1', 'RC2', 'RC3', 'RC4', 'RC5', 'RC6', 'RC7', 'RC8', 'RC9', 'IU1', 'IU2', 'IU3']
    // };

    const formContainer = document.querySelector('#projects-form');

    let html = `
        <div class="form-group">
            <select id="group" class="form-control">
            <option value="" class="dropdown-header">${wsStrings[locale].category}</option>`;
    
    
    html += category.map(
        ( category ) => (             
                `<option value="${category}">${category}</option>`
        )
    ).join('');

    html += `
            </select>
        </div>
        <div class="form-group">
            <select id="research-cluster" class="form-control">
            <option value="" class="dropdown-header">${wsStrings[locale].cluster_iu}</option>`;

    html += clusterOpts.map(
        ( cluster ) => (             
                `<option value="${cluster}">${wsClusters[locale][cluster]}</option>`
        )
    ).join('');

    html += `
            </select>
        </div>
        <div class="form-group">
            <input list="principal-investor-list" id="principal-investor" class="form-control" placeholder="${wsStrings[locale].principal_investigator}">
            <datalist id="principal-investor-list">`;

    html += ips.map(
        (investigator) => (
            `<option class="${investigator.ip_id}" value="${investigator.ip_name}"></option>`
        )
    ).join('');

    html += `
            </datalist>
        </div>`;

    html += `
            </select>
        </div>`;
    html += `
    <div class="form-group form-flex-fields">

            <div class="from"><input type="text" id="start-date" placeholder="${wsStrings[locale].from}" onclick="this.type='date'" onblur="this.type='text'"  onchange="console.log(this.value)"></div>

            <div class="to"><input type="text" id="end-date" placeholder="${wsStrings[locale].to}" onclick="this.type='date'" onblur="this.type='text'"  onchange="console.log(this.value)"></div>

        </div>
        <div class="form-group form-buttons clickable-cta">
        <div class="flecha-container flecha-container-position-center">
            <span class="cuerpo-flecha filter-data"> </span><br>
            <span class="punta-flecha filter-data"> </span><br>
            <span class="text-flecha filter-data">${wsStrings[locale].apply}</span>
        </div>
        <div class="refresh">
            <span class="refresh-icon refresh-filter"></span> <span class="refresh-button refresh-filter">&nbsp;${wsStrings[locale].refresh}</span>
        </div>
    </div>`;
    //console.log(html);
    //console.log(formContainer);
    formContainer.innerHTML = html;
}

/*-----------------------------------------------------------------
    Genera el HTML del listado de publicaciones
------------------------------------------------------------------*/

const generatePublicationsHTML = ({meta, data }) => {

//   "meta": {
//         "limit": 250,
//         "offset": 0,
//         "returned": 250,
//         "total": 2258,
//         "pages": 10
//   }



    const cimneAffiliationID = '60104172'; // CIMNE affiliation ID in CrossRef
    const publicationsContainer = document.querySelector('#publications-container');
    if (!publicationsContainer) return;

    let html = '';
    let vol, issue, range;

    // Lista de publicaciones
    (data || []).forEach((publication) => {

        const {authors = [], title = '', publication_name = '', volume, issue_identifier, page_range, year, doi  } = publication;

        let authors_string = '';
        authors.forEach((author, index) => {
            const { indexed_name = '', aff_id } = author;
            authors_string += `<span class="${aff_id == cimneAffiliationID ? 'cimne-affiliated' : ''}">${indexed_name}${index < authors.length - 1 ? ', ' : ''}</span>`;
        });

        vol = volume ? `vol (${volume}), ` : '';
        issue = issue_identifier ? `issue (${issue_identifier}), ` : '';
        range = page_range ? `pp (${page_range}), ` : '';

        html += `
        <div class="list-block">
            <div class="list-item">
                <p class="publication-authors">${authors_string}</p>
            </div>
            <div class="list-item">
                <p class="publication-title">${title}</p>
            </div>
            <div class="list-item">
                <p class="publication-details"><span class="publication-name">${publication_name}${publication_name ? ', ' : ''}</span><span class="publication-volume">${vol}</span><span class="publication-issue">${issue}</span><span class="publication-page-range">${range}</span><span class="publication-year">${year || ''}${year ? ', ' : ''}</span><span class="publication-doi"><a href="https://doi.org/${doi || ''}" rel="noopener noreferrer" title="Doi hyperlink" class="" target="_blank">${doi || ''}</a></span></p>
            </div>
        </div>
       `;
    });

    // Escribe la lista en el contenedor
    publicationsContainer.innerHTML = html || `<p class="no-results">${wsStrings[locale].no_results || 'No results'}</p>`;

    // --- PAGINACIÓN ---
    // Crear o reutilizar un contenedor para la paginación inmediatamente después del listado
    const paginationId = 'publications-pagination';
    let paginationContainer = document.getElementById(paginationId);
    if (!paginationContainer) {
        paginationContainer = document.createElement('div');
        paginationContainer.id = paginationId;
        paginationContainer.className = 'publications-pagination';
        publicationsContainer.insertAdjacentElement('afterend', paginationContainer);
    }

    // Si no hay meta, no mostramos paginación
    if (!meta) {
        paginationContainer.innerHTML = '';
        return;
    }

    const limit = meta.limit || 0;
    const offset = meta.offset || 0;
    const total = meta.total || 0;
    const pagesFromMeta = meta.pages || null;

    const totalPages = pagesFromMeta || (limit > 0 && total ? Math.ceil(total / limit) : 1);
    const currentPage = (limit > 0) ? Math.floor(offset / limit) + 1 : (meta.page || 1);

    // Rango de páginas a mostrar (centra en la página activa)
    const visible = 2; // número máximo de botones de página a mostrar
    let start = Math.max(1, currentPage - Math.floor(visible / 2));
    let end = Math.min(totalPages, start + visible - 1);
    if ((end - start + 1) < visible) {
        start = Math.max(1, end - visible + 1);
    }

    // Generar HTML de paginación
    let phtml = '<nav class="pagination-block" aria-label="Publications pagination">';

    // Primera
    phtml += `<button class="pub-page-btn first" data-page="1" ${currentPage === 1 ? 'disabled' : ''} aria-label="First page">«</button>`;

    // Anterior
    const prevPage = Math.max(1, currentPage - 1);
    phtml += `<button class="pub-page-btn prev" data-page="${prevPage}" ${currentPage === 1 ? 'disabled' : ''} aria-label="Previous page">‹</button>`;

    // Si el inicio está por encima de 1, mostrar salto a la primera página (num)
    if (start > 1) {
        phtml += `<button class="pub-page-btn" data-page="1">1</button>`;
        if (start > 2) phtml += `<span class="dots">…</span>`;
    }

    // Botones de páginas cercanas
    for (let i = start; i <= end; i++) {
        phtml += `<button class="pub-page-btn page-number ${i === currentPage ? 'active' : ''}" data-page="${i}" aria-current="${i === currentPage ? 'page' : ''}">${i}</button>`;
    }

    // Si el final está antes de la última página, mostrar salto
    if (end < totalPages) {
        if (end < totalPages - 1) phtml += `<span class="dots">…</span>`;
        phtml += `<button class="pub-page-btn" data-page="${totalPages}">${totalPages}</button>`;
    }

    // Siguiente
    const nextPage = Math.min(totalPages, currentPage + 1);
    phtml += `<button class="pub-page-btn next" data-page="${nextPage}" ${currentPage === totalPages ? 'disabled' : ''} aria-label="Next page">›</button>`;

    // Última
    phtml += `<button class="pub-page-btn last" data-page="${totalPages}" ${currentPage === totalPages ? 'disabled' : ''} aria-label="Last page">»</button>`;

    phtml += ` <span class="pagination-info">${wsStrings[locale] && wsStrings[locale].page ? wsStrings[locale].page : 'Page'} ${currentPage} / ${totalPages}</span>`;
    phtml += '</nav>';

    paginationContainer.innerHTML = phtml;
    // Guardar metadatos útiles para el manejador de paginación (limit, total y página actual)
    try {
        paginationContainer.dataset.limit = String(limit);
        paginationContainer.dataset.total = String(total);
        paginationContainer.dataset.currentPage = String(currentPage);
    } catch (e) {
        // Ignorar si no se puede asignar
    }

}

/*-----------------------------------------------------------------
    Genera el HTML del filtro de la página de Publicaciones
------------------------------------------------------------------*/

const generatePublicationsFilter = ( data ) => {

    //console.log('Generating publications filter');
    //console.log(data);
    //console.log(data.rtd_groups);
    

    let groups = '';

    let currentPage = getCurrentPage();

    const subtype = currentPage === 'publications' || currentPage === 'publicaciones' || currentPage === 'publicacions' ? 'filtered' : 'all';

    //console.log("Current page: " + currentPage);
    //console.log("Subtype: " + subtype);

    if (data) { 
        const { rtd_groups } = data;
        //console.log("rtd_grupos en publications filter: " + rtd_groups);

        groups +=`
        <div class="form-group">
            <select id="research-rtd" class="form-control">
            <option value="" class="dropdown-header">${wsStrings[locale].rtd}</option>`;

        groups += rtd_groups.map(
            ({ rtd_id, name }) => (
                `<option value="${rtd_id}">${name}</option>`
            )
        ).join('');
    } else {
        //console.log('No data provided for groups');
    }

    //const clusterOpts = ['RC1', 'RC2', 'RC3', 'RC4', 'RC5', 'RC6', 'RC7', 'RC8', 'RC9', 'IU1', 'IU2', 'IU3'];
    
    // Comentado: primero el código (RC1, RC2, etc.), luego el nombre con guiones a espacios y mayúsculas iniciales
    // const opts_cluster = {
    //     all: [
    //         ['RC1', 'Geomechanics And Hydrogeology'],
    //         ['RC2', 'Machine Learning And Models In Hydro Environmental Engineering'],
    //         ['RC3', 'Aeronautical Marine Automotive And Energy Engineering'],
    //         ['RC4', 'Solid And Fluid Simulation For Industrial Processes'],
    //         ['RC5', 'Computational Mechanics In Medical Engineering And Living Matter'],
    //         ['RC6', 'Structural And Particle Mechanics'],
    //         ['RC7', 'Mechanics Of Advanced Materials And Metamaterials'],
    //         ['RC8', 'Credible High Fidelity And Data Driven Models'],
    //         ['RC9', 'Large Scale Multiphysics Computations'],
    //         ['IU1', 'Centre For Innovation In Transport Cenit'],
    //         ['IU2', 'Building Energy And Environment Beegroup'],
    //         ['IU3', 'Pre Post And Digital Technologies']
    //     ],
    //     filtered: ['RC1', 'RC2', 'RC3', 'RC4', 'RC5', 'RC6', 'RC7', 'RC8', 'RC9', 'IU1', 'IU2', 'IU3']
    // };
    const opts_subtype = {
        all: 
            ["ar", "ch", "cp", "ed", "re"]
        ,
        filtered: ["ar", "ed", "re"]
            // ['ar', 'Article'],
            // ['re', 'Review'],
            // ['ed', 'Editorial']
    };


    const publicationsFormContainer = document.querySelector('#publications-form');
 
    //let test = 'RC1';

    // Mostrar por consola el valor correspondiente al valor de la variable test
    //console.log('wsClusters[locale][test]: ' + wsClusters[locale][test]);

    let html = "";

    // let html = `
    //     <div class="form-group">
    //         <select id="research-cluster" class="form-control">
    //         <option value="" class="dropdown-header">${wsStrings[locale].cluster_iu}</option>`;

    // html += clusterOpts.map(
    //     ( cluster ) => (             
    //             `<option value="${cluster}">${wsClusters[locale][cluster]}</option>`
    //     )
    // ).join('');

    // html += `
    //         </select>
    //         </div>`;

    // html += `
    //     <div class="form-group">
    //         <select id="research-rtd" class="form-control">
    //         <option value="" class="dropdown-header">${wsStrings[locale].rtd}</option>`;

    html += groups ? groups : '';   
    

    html += `
            </select>
            </div>`;

    html += ` 
        <div class="form-group">
            <select id="publication-type" class="form-control">
            <option value="" class="dropdown-header">${wsStrings[locale].publication_type}</option>`;

    html += opts_subtype[subtype].map(
        (publication) => (
            `<option value="${publication}">${wsPublicationTypes[locale][publication]}</option>`
        )
    ).join('');

    html += `
            </select>
        </div>`;

    html += `
        <div class="form-group form-flex-fields">

            <div class="from"><input type="text" id="start-date" placeholder="${wsStrings[locale].from}" onclick="this.type='date'" onblur="this.type='text'"  onchange="console.log(this.value)"></div>

            <div class="to"><input type="text" id="end-date" placeholder="${wsStrings[locale].to}" onclick="this.type='date'" onblur="this.type='text'"  onchange="console.log(this.value)"></div>

        </div>
        <div class="form-group form-buttons clickable-cta">
        <div class="flecha-container flecha-container-position-center">
            <span class="cuerpo-flecha filter-data"> </span><br>
            <span class="punta-flecha filter-data"> </span><br>
            <span class="text-flecha filter-data">${wsStrings[locale].apply}</span>
        </div>
        <div class="refresh">
            <span class="refresh-icon refresh-filter"></span> <span class="refresh-button refresh-filter">&nbsp;${wsStrings[locale].refresh}</span>
        </div>
    </div>`;
    //console.log(html);
    //console.log(formContainer);
    publicationsFormContainer.innerHTML = html;
}

/*-----------------------------------------------------------------
    Genera el HTML de la página de Directorio
------------------------------------------------------------------*/

const generateDirectoryHTML = ({ personal }) => {

    const container = document.querySelector('#staff-container');
	let emailString = '';
    let html = '<div class="staff-block">';

    personal.forEach(({ first_name, last_name, last_name2, email, phone,  person_id, category }) => {
        // ${locale == 'en' ? '' : locale + '/'} - añadir locale en el link si no es inglés
        //emailString = email ? getMailCimne(email) : 'No Email';
		html += 
        `<div class="staff-item">
            <p id="${person_id}" class="staff-name open-profile"><a href="/${locale == 'en' ? '' : locale + '/'}about/directory/staff-profile/?id=${person_id}">${first_name} ${last_name} ${last_name2}</a></p>
            <p class="staff-phone">${phone ? phone : 'No phone'}</p>
            <p class="staff-category">${category ? category : 'No category'}</p>
            <p id="${email}" class="staff-email copy-email-to-clipboard">${wsStrings[locale].copy_email}</p>
        </div>`;
    });

    html += '</div>';


    container.innerHTML = html;
}
/*-----------------------------------------------------------------
    Genera el HTML de ficha de personal del Directorio
------------------------------------------------------------------*/

const generateStaffProfileHTML = ({ IDSIGPro, Nombre, Apellido1, Apellido2, Email, Telefono1, ORCID, RID, LinkGoogle, SCOPUS, Position, RTDGrupo1, Foto, NombreSede, DireccionSede, CiudadSede, ProvinciaSede, PaisSede, CPSede, Despacho, CV }) => {

    
    const capitalize = str => str ? str.charAt(0).toUpperCase() + str.slice(1).toLowerCase() : '';
    const full_name = `${Nombre} ${capitalize(Apellido1)} ${capitalize(Apellido2)}`;
    const address = `${DireccionSede || ''}, </br> ${CPSede}, ${CiudadSede || ''}, ${ProvinciaSede || ''}, ${PaisSede || ''}.`.trim().replace(/ ,/g, '');
    const profileImg = Foto ? `data:image/jpg;base64,${Foto}` : '/wp-content/uploads/2024/12/Hidden_profile_pic.png';
    const container = document.querySelector('#staff-profile-container');
    const icsButton = document.querySelector('#download-profile');
    const icsAttributes = {
        'data-name': Nombre,
        'data-surename': `${Apellido1} ${Apellido2}`,
        'data-position': Position,
        'data-email': getMailCimne(Email),
        'data-phone': Telefono1,
        'data-address': address.replace(/<\/br>/g, ', ')
    };
    
    function setAttributes(element, attributes) {
        for (const [key, value] of Object.entries(attributes)) {
            element.setAttribute(key, value);
        }
    }

    icsButton ? setAttributes(icsButton, icsAttributes) : null;



    let html = `
<div class="staff-profile">
<div class="staff-ficha-back">
            <div><span class="back">${wsStrings[locale].back}</span></div>
        </div>
    <div class="staff-profile-header">
        <div class="column-left">
            <div class="staff-profile-header-image">
                <img src="${profileImg}" alt="Staff Image">
            </div>
        </div>
        <div class="column-right">
            <div class="staff-profile-header-info">`;

    //full_name = full_name ? full_name : `${Nombre || ''} ${Apellido1 || ''} ${Apellido2 || ''}`;

    html += full_name ? `<h4 class="staff-profile-header-name">${full_name}</h4>` : '';
    
    html += Email ? `<p id="${Email}" class="staff-email copy-email-to-clipboard">${wsStrings[locale].copy_email}</p>` : '';
    html += Telefono1 ? `<p class="staff-profile-header-tlf">${Telefono1 || ''}</p>` : '';
    html += NombreSede ? `<h5 class="staff-profile-header-branch">${wsStrings[locale].branch}</h5>
                       <p class="staff-profile-branch">${NombreSede}</p>` : '';
    html += Despacho ? `<h5 class="staff-profile-header-office">${wsStrings[locale].office}</h5>
                       <p class="staff-profile-office">${Despacho}</p>` : '';
    html += address ? `<h5 class="staff-profile-header-address">${wsStrings[locale].address}</h5>
                     <p class="staff-profile-address">${address}</p>` : '';
    
  
    html += `
            </div>
        </div>
    </div>
    <div class="staff-profile-content">
        `;
    html += RTDGrupo1 ? `
        <div class="row">
            <div class="column-left">
                    <h5>${wsStrings[locale].rtd_group}</h5>
            </div>
            <div class="column-right">
                    <p>${RTDGrupo1 || ''}</p>
            </div>
        </div>` : '';
    html += Position ? `
        <div class="row">
            <div class="column-left">
                    <h5>${wsStrings[locale].position}</h5>
            </div>
            <div class="column-right">
                    <p>${Position || ''}</p>
            </div>
        </div>` : '';
    html += SCOPUS ? `
        <div class="row">
            <div class="column-left">
                    <h5>Scopus ID</h5>
            </div>
            <div class="column-right">
                    <p><a href="https://www.scopus.com/authid/detail.uri?authorId=${typeof SCOPUS !== 'undefined' ? SCOPUS : ''}" target="_blank" rel="">${typeof SCOPUS !== 'undefined' ? SCOPUS : ''}</a></p>
            </div>
        </div>` : '';
    html += ORCID ? `
        <div class="row">
            <div class="column-left">
                    <h5>ORCID</h5>
            </div>
            <div class="column-right">
                    <p><a href="https://orcid.org/${typeof ORCID !== 'undefined' ? ORCID : ''}" target="_blank" rel="">${typeof ORCID !== 'undefined' ? ORCID : ''}</a></p>
            </div>
        </div>` : '';
    html += RID ? `
        <div class="row">
            <div class="column-left">
                    <h5>${wsStrings[locale].researcher_id}</h5>
            </div>
            <div class="column-right">
                    <p><a href="http://www.researcherid.com/rid/${typeof RID !== 'undefined' ? RID : ''}" target="_blank" rel="">${typeof RID !== 'undefined' ? RID : ''}</a></p>
            </div>
        </div>` : '';
    html += LinkGoogle ? `
        <div class="row">
            <div class="column-left">
                    <h5>Google Scholar</h5>
            </div>
            <div class="column-right">
                    <p><a href="https://scholar.google.com/citations?user=${typeof LinkGoogle !== 'undefined' ? LinkGoogle : '#'}" target="_blank" rel="">${typeof LinkGoogle !== 'undefined' ? LinkGoogle : ''}</a></p>
            </div>
        </div>` : '';
    html += CV ? `
        <div class="row">
            <div class="column-left">
                    <h5>CV</h5>
            </div>
            <div class="column-right">
                    <p>${CV || ''}</p>
            </div>
        </div>` : '';
    html += `
            </div>
        </div>    
        `;
    //console.log(html);
    container.innerHTML = html;
}


