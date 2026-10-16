
/*=========================================================================
 * Nav tabs
 *
 * Sistema de pestañas independiente del módulo Tabs de Divi.
 *
 * Convención:
 *
 *   Tab:
 *   id="publications-item"
 *   class="tab-item"
 *
 *   Contenido:
 *   class="publications-content tab-content"
 *
 * También permite abrir directamente una pestaña mediante:
 *
 *   ?tab=publications
 *=========================================================================*/

function initNavTabs() {
  const ACTIVE_CLASS = 'nav-item-active';
  const ITEM_SUFFIX = '-item';
  const CONTENT_SUFFIX = '-content';

  const tabItems = Array.from(
    document.querySelectorAll('.tab-item')
  );

  const tabContents = Array.from(
    document.querySelectorAll('.tab-content')
  );

  /*
   * Si la página no utiliza este sistema de tabs,
   * no hacemos nada.
   */
  if (!tabItems.length || !tabContents.length) {
    return;
  }


  /**
   * Obtiene la clase del contenido correspondiente a una pestaña.
   *
   * publications-item → publications-content
   *
   * @param {HTMLElement} tabItem
   * @return {string|null}
   */
  function getTabContentClass(tabItem) {
    const tabId = tabItem.id;

    if (!tabId || !tabId.endsWith(ITEM_SUFFIX)) {
      return null;
    }

    return (
      tabId.slice(0, -ITEM_SUFFIX.length) +
      CONTENT_SUFFIX
    );
  }


  /**
   * Activa una pestaña y muestra su contenido.
   *
   * @param {HTMLElement} tabItem
   * @return {boolean}
   */
  function activateTab(tabItem) {
    const contentClass = getTabContentClass(tabItem);

    if (!contentClass) {
      return false;
    }

    /*
     * Puede haber más de un elemento asociado a la misma pestaña,
     * igual que ocurría con $('.nombre-clase').show().
     */
    const matchingContents = tabContents.filter((content) =>
      content.classList.contains(contentClass)
    );

    if (!matchingContents.length) {
      return false;
    }


    /*
     * Estado de navegación.
     */
    tabItems.forEach((item) => {
      item.classList.toggle(
        ACTIVE_CLASS,
        item === tabItem
      );
    });


    /*
     * Ocultar todos los contenidos.
     */
    tabContents.forEach((content) => {
      content.style.display = 'none';
    });


    /*
     * Mostrar el contenido correspondiente.
     *
     * Dejamos display vacío en lugar de forzar "block",
     * para respetar el display definido por CSS.
     */
    matchingContents.forEach((content) => {
      content.style.display = '';
    });

    return true;
  }


  /**
   * Obtiene la pestaña solicitada mediante ?tab=xxxx.
   *
   * @return {HTMLElement|null}
   */
  function getRequestedTab() {
    const params = new URLSearchParams(
      window.location.search
    );

    const tabName = params.get('tab');

    if (!tabName) {
      return null;
    }

    const tabItem = document.getElementById(
      `${tabName}${ITEM_SUFFIX}`
    );

    if (
      !tabItem ||
      !tabItem.classList.contains('tab-item')
    ) {
      return null;
    }

    return tabItem;
  }


  /*
   * Ocultar inicialmente todos los contenidos.
   */
  tabContents.forEach((content) => {
    content.style.display = 'none';
  });


  /*
   * Activación mediante click.
   */
  tabItems.forEach((tabItem) => {
    tabItem.addEventListener('click', () => {
      activateTab(tabItem);
    });
  });


  /*
   * Pestaña inicial:
   *
   * 1. La indicada mediante ?tab=xxxx.
   * 2. La primera pestaña si el parámetro no existe o no es válido.
   */
  const requestedTab = getRequestedTab();

  if (
    requestedTab &&
    activateTab(requestedTab)
  ) {
    return;
  }

  activateTab(tabItems[0]);
}


initNavTabs();

/*=========================================================================
 * Projects filter
 *
 * Filtra los proyectos por estado:
 *
 *   #ongoing-projects-item   → .ongoing-project
 *   #finished-projects-item  → .finished-project
 *
 * Los proyectos no pertenecientes al filtro activo reciben
 * la clase .hide-element.
 *=========================================================================*/

function initProjectsFilter() {
  const menu = document.getElementById('project-items');

  if (!menu) {
    return;
  }

  const ACTIVE_CLASS = 'nav-item-active';
  const HIDDEN_CLASS = 'hide-element';

  const FILTERS = {
    'ongoing-projects-item': 'ongoing-project',
    'finished-projects-item': 'finished-project'
  };


  /**
   * Activa visualmente una pestaña del filtro.
   *
   * @param {HTMLElement} activeTab
   * @return {void}
   */
  function setActiveProjectTab(activeTab) {
    menu.querySelectorAll('.projects-nav-item').forEach((tab) => {
      tab.classList.toggle(
        ACTIVE_CLASS,
        tab === activeTab
      );
    });
  }


  /**
   * Muestra únicamente los proyectos que pertenecen
   * a la categoría indicada.
   * 
   * se podrá eliminar con la nueva api
   *
   * @param {string} projectClass
   * @return {void}
   */
  // function filterProjects(projectClass) {
  //   document.querySelectorAll('.list-block').forEach((project) => {
  //     const shouldShow =
  //       project.classList.contains(projectClass);

  //     project.classList.toggle(
  //       HIDDEN_CLASS,
  //       !shouldShow
  //     );
  //   });
  // }


  /**
   * Gestiona los clicks mediante delegación de eventos.
   */
  menu.addEventListener('click', (event) => {
    const selectedTab = event.target.closest(
      '.projects-nav-item'
    );

    if (!selectedTab || !menu.contains(selectedTab)) {
      return;
    }

    /*
     * Si la pestaña ya está activa no es necesario
     * volver a ejecutar el filtro.
     */
    if (selectedTab.classList.contains(ACTIVE_CLASS)) {
      return;
    }

    const projectClass = FILTERS[selectedTab.id];

    /*
     * Ignorar elementos que no formen parte de los
     * filtros definidos.
     */
    if (!projectClass) {
      return;
    }

    setActiveProjectTab(selectedTab);
    filterProjects(projectClass);
  });
}


initProjectsFilter();

/*=========================================================================
 * Toggle tabs
 *
 * Convención:
 *
 *   Disparador:
 *   id="toggle-trigger-xxxxx"
 *
 *   Objetivo:
 *   id="toggle-target-xxxxx"
 *
 * Opcionalmente:
 *
 *   - Una imagen .square-form-img puede contener el atributo
 *     data-toggle-src para intercambiar su imagen.
 *
 *   - El disparador puede contener una clase:
 *     parent-background-color-xxxxx
 *
 *     que activará en su elemento padre:
 *     background-parent-active-xxxxx
 *=========================================================================*/

function initToggleTabs() {
  const TRIGGER_SELECTOR = '[id*="toggle-trigger"]';

  const TRIGGER_ID_PART = 'toggle-trigger';
  const TARGET_ID_PART = 'toggle-target';

  const IMAGE_SELECTOR = '.square-form-img';
  const IMAGE_TOGGLE_ATTRIBUTE = 'data-toggle-src';

  const PARENT_COLOR_PREFIX = 'parent-background-color';
  const PARENT_ACTIVE_PREFIX = 'background-parent-active';

  /*
   * jQuery "slow" equivale aproximadamente a 600 ms.
   */
  const ANIMATION_DURATION = 600;

  const triggers = document.querySelectorAll(TRIGGER_SELECTOR);

  if (!triggers.length) {
    return;
  }

  /*
   * Estado de apertura de cada target.
   *
   * WeakMap evita añadir propiedades personalizadas
   * directamente a los elementos DOM.
   */
  const toggleStates = new WeakMap();

  /*
   * Animación activa de cada target.
   *
   * Permite cancelar limpiamente una animación si el
   * usuario pulsa varias veces rápidamente.
   */
  const activeAnimations = new WeakMap();


  /**
   * Obtiene la parte de una clase situada después de un prefijo.
   *
   * Ejemplo:
   *
   * parent-background-color-blue
   *                         ↓
   *                       -blue
   *
   * @param {HTMLElement} element
   * @param {string} prefix
   * @return {string|null}
   */
  function getClassSuffix(element, prefix) {
    for (const className of element.classList) {
      if (className.startsWith(prefix)) {
        return className.slice(prefix.length);
      }
    }

    return null;
  }


  /**
   * Obtiene el target asociado a un trigger.
   *
   * toggle-trigger-example
   *          ↓
   * toggle-target-example
   *
   * @param {HTMLElement} trigger
   * @return {HTMLElement|null}
   */
  function getToggleTarget(trigger) {
    if (
      !trigger.id ||
      !trigger.id.includes(TRIGGER_ID_PART)
    ) {
      return null;
    }

    const targetId = trigger.id.replace(
      TRIGGER_ID_PART,
      TARGET_ID_PART
    );

    return document.getElementById(targetId);
  }


  /**
   * Intercambia src y data-toggle-src de una imagen.
   *
   * @param {HTMLImageElement} image
   * @return {void}
   */
  function toggleImageSource(image) {
    const currentSource = image.getAttribute('src');
    const alternateSource = image.getAttribute(
      IMAGE_TOGGLE_ATTRIBUTE
    );

    if (!currentSource || !alternateSource) {
      return;
    }

    image.setAttribute('src', alternateSource);
    image.setAttribute(
      IMAGE_TOGGLE_ATTRIBUTE,
      currentSource
    );
  }


  /**
   * Activa/desactiva la clase de fondo del elemento padre.
   *
   * @param {HTMLElement} trigger
   * @return {void}
   */
  function toggleParentBackground(trigger) {
    const colorSuffix = getClassSuffix(
      trigger,
      PARENT_COLOR_PREFIX
    );

    if (colorSuffix === null || !trigger.parentElement) {
      return;
    }

    trigger.parentElement.classList.toggle(
      `${PARENT_ACTIVE_PREFIX}${colorSuffix}`
    );
  }


  /**
   * Cancela una animación anterior y limpia los estilos
   * temporales utilizados para ella.
   *
   * @param {HTMLElement} target
   * @return {void}
   */
  function cancelActiveAnimation(target) {
    const animation = activeAnimations.get(target);

    if (animation) {
      animation.onfinish = null;
      animation.cancel();

      activeAnimations.delete(target);
    }

    target.style.height = '';
    target.style.opacity = '';
    target.style.overflow = '';
  }


  /**
   * Muestra un target con una animación equivalente al
   * antiguo jQuery toggle("slow").
   *
   * @param {HTMLElement} target
   * @return {void}
   */
  function openToggleTarget(target) {
    cancelActiveAnimation(target);

    /*
     * Primero restauramos el display definido por CSS.
     */
    target.style.display = '';

    /*
     * Si una regla CSS mantiene el elemento oculto,
     * utilizamos block como fallback.
     *
     * Los targets actuales son contenedores DIV, por lo que
     * equivale al comportamiento que realizaba jQuery.
     */
    if (window.getComputedStyle(target).display === 'none') {
      target.style.display = 'block';
    }

    const targetHeight = target.scrollHeight;

    target.style.overflow = 'hidden';

    const animation = target.animate(
      [
        {
          height: '0px',
          opacity: 0
        },
        {
          height: `${targetHeight}px`,
          opacity: 1
        }
      ],
      {
        duration: ANIMATION_DURATION,
        easing: 'ease'
      }
    );

    activeAnimations.set(target, animation);

    animation.onfinish = () => {
      target.style.height = '';
      target.style.opacity = '';
      target.style.overflow = '';

      activeAnimations.delete(target);
    };
  }


  /**
   * Oculta un target con animación.
   *
   * @param {HTMLElement} target
   * @return {void}
   */
  function closeToggleTarget(target) {
    cancelActiveAnimation(target);

    const currentHeight =
      target.getBoundingClientRect().height;

    target.style.overflow = 'hidden';

    const animation = target.animate(
      [
        {
          height: `${currentHeight}px`,
          opacity: 1
        },
        {
          height: '0px',
          opacity: 0
        }
      ],
      {
        duration: ANIMATION_DURATION,
        easing: 'ease'
      }
    );

    activeAnimations.set(target, animation);

    animation.onfinish = () => {
      target.style.display = 'none';
      target.style.height = '';
      target.style.opacity = '';
      target.style.overflow = '';

      activeAnimations.delete(target);
    };
  }


  /**
   * Cambia el estado abierto/cerrado de un target.
   *
   * @param {HTMLElement} trigger
   * @param {HTMLElement} target
   * @return {void}
   */
  function toggleTarget(trigger, target) {
    const isOpen = toggleStates.get(target) === true;
    const shouldOpen = !isOpen;

    toggleStates.set(target, shouldOpen);

    trigger.setAttribute(
      'aria-expanded',
      String(shouldOpen)
    );

    target.setAttribute(
      'aria-hidden',
      String(!shouldOpen)
    );

    if (shouldOpen) {
      openToggleTarget(target);
    } else {
      closeToggleTarget(target);
    }
  }


  /**
   * Inicializa un trigger y su target asociado.
   *
   * @param {HTMLElement} trigger
   * @return {void}
   */
  function initToggleTrigger(trigger) {
    const target = getToggleTarget(trigger);

    /*
     * Un trigger sin target válido se ignora.
     */
    if (!target) {
      return;
    }

    const initiallyOpen =
      window.getComputedStyle(target).display !== 'none';

    toggleStates.set(target, initiallyOpen);

    trigger.setAttribute(
      'aria-expanded',
      String(initiallyOpen)
    );

    target.setAttribute(
      'aria-hidden',
      String(!initiallyOpen)
    );


    trigger.addEventListener('click', () => {
      toggleParentBackground(trigger);

      const image = trigger.querySelector(
        IMAGE_SELECTOR
      );

      if (image) {
        toggleImageSource(image);
      }

      toggleTarget(trigger, target);
    });
  }


  triggers.forEach(initToggleTrigger);
}


initToggleTabs();

/*=========================================================================
 * Image effects
 *
 * Añade elementos gráficos utilizados en diferentes componentes:
 *
 * - Etiquetas de categoría en noticias.
 * - Iconos SDG.
 * - Formas cuadradas para toggles, módulos de blog y enlaces.
 *=========================================================================*/

(() => {
  'use strict';

  const UPLOADS_BASE = '/wp-content/uploads';

  const ASSETS = {
    squareCross:
      `${UPLOADS_BASE}/2025/02/cross-bg-transparent.png`,

    squareDash:
      `${UPLOADS_BASE}/2025/03/dash-bg-transparent.png`,

    squareArrowLeft:
      `${UPLOADS_BASE}/2025/02/left-arrow-bg-transparent.png`
  };


  const SDG_ICONS = [
    'sdg-03-en',
    'sdg-07-en',
    'sdg-09-en',
    'sdg-11-en',
    'sdg-13-en'
  ];


  /*
   * Categorías que pueden aparecer como etiqueta visual
   * en los artículos.
   *
   * Se utiliza Set porque solo necesitamos comprobar
   * si una categoría pertenece a la lista.
   */
  const NEWS_CATEGORIES = new Set([
    'in depth',
    'research',
    'innovation',
    'institutional',

    'a fondo',
    'investigación',
    'innovación',

    'a fons',
    'recerca',
    'innovació',

    'coffe talk',

    'conferencias',
    'conferences',
    'conferències',

    'congress',
    'congrés',
    'congreso',

    'seminars',
    'seminarios',
    'seminaris',

    'workshops',

    'thesis defense',
    'defensa de tesis',
    'defensa de tesi'
  ]);


  /**
   * Crea una imagen con los atributos comunes utilizados
   * por este módulo.
   *
   * @param {Object} options
   * @param {string} options.src
   * @param {string} options.alt
   * @param {string} options.className
   * @param {string|null} [options.toggleSrc]
   *
   * @return {HTMLImageElement}
   */
  function createImage({
    src,
    alt,
    className,
    toggleSrc = null
  }) {
    const image = document.createElement('img');

    image.src = src;
    image.alt = alt;
    image.className = className;

    if (toggleSrc) {
      image.dataset.toggleSrc = toggleSrc;
    }

    return image;
  }


  /**
   * Obtiene la categoría visual de un artículo.
   *
   * Se mantiene el mismo comportamiento del código original:
   * si existen varias categorías válidas, se utiliza la última.
   *
   * @param {HTMLElement} article
   *
   * @return {string}
   */
  function getArticleCategory(article) {
    let selectedCategory = 'Uncategorized';

    article
      .querySelectorAll('p.post-meta a')
      .forEach((link) => {
        const category = link.textContent.trim();

        if (
          NEWS_CATEGORIES.has(
            category.toLowerCase()
          )
        ) {
          selectedCategory = category;
        }
      });

    return selectedCategory;
  }


  /**
   * Añade la etiqueta rectangular de categoría
   * delante de la imagen de cada artículo.
   *
   * @return {void}
   */
  function addRectangleLabels() {
    document
      .querySelectorAll('.section-news article')
      .forEach((article) => {
        const image = article.querySelector('img');

        if (!image || !image.parentElement) {
          return;
        }


        /*
         * Evitar duplicados si la inicialización se ejecutara
         * accidentalmente más de una vez.
         */
        const previousElement =
          image.previousElementSibling;

        if (
          previousElement?.classList.contains(
            'rectangle'
          )
        ) {
          return;
        }


        const rectangle =
          document.createElement('div');

        const label =
          document.createElement('span');

        rectangle.className = 'rectangle';
        label.className = 'rectangle-text';

        label.textContent =
          getArticleCategory(article);

        rectangle.append(label);

        image.before(rectangle);
      });
  }


  /**
   * Añade los iconos SDG correspondientes a las clases
   * presentes en cada contenedor.
   *
   * @return {void}
   */
  function addSdgIcons() {
    document
      .querySelectorAll('.sdg-icon')
      .forEach((container) => {

        SDG_ICONS.forEach((icon) => {
          if (!container.classList.contains(icon)) {
            return;
          }


          /*
           * Evitar añadir dos veces el mismo icono.
           */
          const existingIcon =
            container.querySelector(
              `img.sdg-icon-img[src*="${icon}"]`
            );

          if (existingIcon) {
            return;
          }


          const image = createImage({
            src:
              `${UPLOADS_BASE}/2025/10/${icon}.png`,

            alt: icon,

            className:
              'sdg-icon-img'
          });

          container.append(image);
        });
      });
  }


  /**
   * Añade la forma cross/dash a los elementos que
   * funcionan como toggle.
   *
   * @return {void}
   */
  function addToggleSquareForms() {
    document
      .querySelectorAll(
        '[class*="img-square"].square-form-cross'
      )
      .forEach((element) => {

        if (
          element.querySelector(
            'img.square-form-img'
          )
        ) {
          return;
        }


        const image = createImage({
          src: ASSETS.squareCross,

          toggleSrc: ASSETS.squareDash,

          alt: 'square-cross-form',

          className: 'square-form-img'
        });

        element.append(image);
      });
  }


  /**
   * Añade la forma cross/dash a las imágenes utilizadas
   * en módulos Blog.
   *
   * @return {void}
   */
  function addBlogSquareForms() {
    document
      .querySelectorAll(
        '[class*="img-square"].img-category-container img'
      )
      .forEach((referenceImage) => {

        const parent =
          referenceImage.parentElement;

        if (!parent) {
          return;
        }


        /*
         * El contenedor debe tener una única forma cuadrada.
         */
        if (
          parent.querySelector(
            'img.square-form-img'
          )
        ) {
          return;
        }


        const image = createImage({
          src: ASSETS.squareCross,

          toggleSrc: ASSETS.squareDash,

          alt: 'square-cross-form',

          className: 'square-form-img'
        });

        referenceImage.after(image);
      });
  }


  /**
   * Añade la forma de flecha izquierda.
   *
   * @return {void}
   */
  function addArrowSquareForms() {
    document
      .querySelectorAll(
        '[class*="img-square"].square-form-arrow-left a'
      )
      .forEach((link) => {

        if (
          link.querySelector(
            'img.square-form-img'
          )
        ) {
          return;
        }


        const image = createImage({
          src: ASSETS.squareArrowLeft,

          alt: 'square-arrow-form',

          className: 'square-form-img'
        });

        link.append(image);
      });
  }


  /**
   * Inicializa todas las formas cuadradas.
   *
   * @return {void}
   */
  function addSquareForms() {
    addToggleSquareForms();
    addBlogSquareForms();
    addArrowSquareForms();
  }


  /**
   * Inicializa los efectos que realmente se utilizan
   * en la página actual.
   *
   * @return {void}
   */
  function initImageEffects() {
    /*
     * Mantenemos las mismas condiciones de activación
     * que tenía el código original.
     */
    if (
      document.querySelector(
        '.img-square-container'
      )
    ) {
      addSquareForms();
    }


    if (
      document.querySelector(
        '.section-news'
      )
    ) {
      addRectangleLabels();
    }


    if (
      document.querySelector(
        '.sdg-icon'
      )
    ) {
      addSdgIcons();
    }
  }


  /*
   * Este bloque ya no depende de jQuery.
   *
   * También funcionará correctamente cuando posteriormente
   * eliminemos el wrapper jQuery general de functions.js.
   */
  if (document.readyState === 'loading') {
    document.addEventListener(
      'DOMContentLoaded',
      initImageEffects,
      { once: true }
    );

    return;
  }

  initImageEffects();

})();

/*=========================================================================
 * Scroll to intro
 *
 * Desplaza la página hasta #intro al pulsar cualquiera de los elementos
 * .flecha-container-move-down.
 *=========================================================================*/

function initMoveDownArrow() {
  const arrows = document.querySelectorAll(
    '.flecha-container-move-down'
  );

  const intro = document.getElementById('intro');

  /*
   * Si la página no contiene la flecha o la sección destino,
   * no es necesario hacer nada.
   */
  if (!arrows.length || !intro) {
    return;
  }

  arrows.forEach((arrow) => {
    arrow.addEventListener('click', () => {
      intro.scrollIntoView({
        behavior: 'auto',
        block: 'start'
      });
    });
  });
}


initMoveDownArrow();

/*=========================================================================
 * Content filters
 *
 * Sistema común de filtrado para:
 *
 * - Buscador de texto #searchBox.
 * - Filtro por fecha de tesis.
 * - Botón #refresh-filter.
 *
 * Elementos buscables:
 *
 * - .blog-area article
 * - .list-block
 * - .staff-item
 * - .theses-list
 *
 * En la página de tesis, búsqueda y fechas se combinan:
 *
 *   coincide con texto
 *          +
 *   coincide con fecha
 *          ↓
 *      se muestra
 *=========================================================================*/

function initContentFilters() {
  const SEARCH_DELAY = 300;

  const searchInput =
    document.getElementById('searchBox');

  const resetButton =
    document.getElementById('refresh-filter');

  const thesesContainer =
    document.getElementById('theses-container');

  const startDateInput =
    document.getElementById('start-date');

  const endDateInput =
    document.getElementById('end-date');

  const applyDateButton =
    document.getElementById('apply-filter');


  /*
   * Si la página no utiliza ninguno de estos controles,
   * no hay nada que inicializar.
   */
  if (
    !searchInput &&
    !resetButton &&
    !thesesContainer
  ) {
    return;
  }


  /*
   * El filtro de fechas solo cambia cuando el usuario
   * pulsa Apply.
   *
   * Esto permite escribir fechas sin aplicar inmediatamente
   * el filtro.
   */
  let appliedStartDate = null;
  let appliedEndDate = null;


  /**
   * Normaliza un texto para búsquedas.
   *
   * - minúsculas
   * - elimina acentos
   *
   * investigación → investigacion
   *
   * @param {string} text
   * @return {string}
   */
  function normalizeSearchText(text = '') {
    return String(text)
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');
  }


  /**
   * Ejecuta una función después de un periodo sin nuevas
   * llamadas.
   *
   * @param {Function} callback
   * @param {number} delay
   *
   * @return {Function}
   */
  function debounce(callback, delay) {
    let timer = null;

    return (...args) => {
      if (timer !== null) {
        clearTimeout(timer);
      }

      timer = window.setTimeout(() => {
        timer = null;
        callback(...args);
      }, delay);
    };
  }


  /**
   * Obtiene los elementos sobre los que debe actuar
   * el buscador en la página actual.
   *
   * Se realiza la consulta cada vez que se filtra para
   * admitir contenido añadido dinámicamente.
   *
   * @return {NodeListOf<HTMLElement>}
   */
  function getFilterItems() {
    const blogArticles =
      document.querySelectorAll(
        '.blog-area article'
      );

    /*
     * Conservamos el comportamiento existente:
     * si existe un área Blog, el buscador trabaja
     * exclusivamente con sus artículos.
     */
    if (blogArticles.length) {
      return blogArticles;
    }

    return document.querySelectorAll(
      '.list-block, .staff-item, .theses-list'
    );
  }


  /**
   * Crea un valor numérico comparable para una fecha.
   *
   * Ejemplo:
   *
   * 12/08/2026 → 20260812
   *
   * También valida que la fecha exista realmente.
   *
   * @param {number} year
   * @param {number} month
   * @param {number} day
   *
   * @return {number|null}
   */
  function createDateKey(year, month, day) {
    if (
      !Number.isInteger(year) ||
      !Number.isInteger(month) ||
      !Number.isInteger(day)
    ) {
      return null;
    }

    const date = new Date(
      Date.UTC(year, month - 1, day)
    );

    /*
     * Evitar que JavaScript transforme automáticamente
     * fechas inválidas.
     *
     * Por ejemplo:
     * 31/02/2026 → marzo.
     */
    if (
      date.getUTCFullYear() !== year ||
      date.getUTCMonth() !== month - 1 ||
      date.getUTCDate() !== day
    ) {
      return null;
    }

    return (
      year * 10000 +
      month * 100 +
      day
    );
  }


  /**
   * Convierte la fecha de un input type="date":
   *
   * YYYY-MM-DD
   *
   * @param {string} value
   * @return {number|null}
   */
  function parseInputDate(value) {
    if (!value) {
      return null;
    }

    const match = value.match(
      /^(\d{4})-(\d{2})-(\d{2})$/
    );

    if (!match) {
      return null;
    }

    return createDateKey(
      Number(match[1]),
      Number(match[2]),
      Number(match[3])
    );
  }


  /**
   * Convierte una fecha mostrada en las tesis:
   *
   * DD/MM/YYYY
   *
   * @param {string} value
   * @return {number|null}
   */
  function parseThesisDate(value) {
    if (!value) {
      return null;
    }

    const match = value.match(
      /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/
    );

    if (!match) {
      return null;
    }

    return createDateKey(
      Number(match[3]),
      Number(match[2]),
      Number(match[1])
    );
  }


  /**
   * Obtiene la fecha Reading de una tesis.
   *
   * Mantiene la estructura utilizada actualmente:
   *
   * <dt>Reading:</dt>
   * <dd>DD/MM/YYYY</dd>
   *
   * @param {HTMLElement} thesis
   * @return {number|null}
   */
  function getThesisReadingDate(thesis) {
    const readingLabel =
      thesis.querySelector('.list-item dt');

    if (
      !readingLabel ||
      readingLabel.textContent.trim() !== 'Reading:'
    ) {
      return null;
    }

    const readingValue =
      readingLabel.nextElementSibling;

    if (!readingValue) {
      return null;
    }

    return parseThesisDate(
      readingValue.textContent.trim()
    );
  }


  /**
   * Comprueba si un elemento coincide con la búsqueda.
   *
   * @param {HTMLElement} item
   * @param {string} query
   *
   * @return {boolean}
   */
  function matchesSearch(item, query) {
    if (!query) {
      return true;
    }

    const itemText =
      normalizeSearchText(item.textContent);

    return itemText.includes(query);
  }


  /**
   * Comprueba si una tesis está dentro del rango
   * de fechas aplicado.
   *
   * Los extremos son inclusivos.
   *
   * @param {HTMLElement} item
   * @return {boolean}
   */
  function matchesDateRange(item) {
    /*
     * El filtro de fechas solamente afecta a tesis.
     */
    if (
      !thesesContainer ||
      !item.classList.contains('theses-list')
    ) {
      return true;
    }


    /*
     * Sin fechas aplicadas no existe filtro temporal.
     */
    if (
      appliedStartDate === null &&
      appliedEndDate === null
    ) {
      return true;
    }


    const readingDate =
      getThesisReadingDate(item);

    /*
     * Si hay filtro activo pero la tesis no contiene
     * una fecha Reading válida, se oculta.
     */
    if (readingDate === null) {
      return false;
    }


    if (
      appliedStartDate !== null &&
      readingDate < appliedStartDate
    ) {
      return false;
    }


    if (
      appliedEndDate !== null &&
      readingDate > appliedEndDate
    ) {
      return false;
    }

    return true;
  }


  /**
   * Aplica conjuntamente todos los filtros activos.
   *
   * @return {void}
   */
  function applyFilters() {
    const query = normalizeSearchText(
      searchInput?.value || ''
    );

    const items = getFilterItems();

    items.forEach((item) => {
      const shouldShow =
        matchesSearch(item, query) &&
        matchesDateRange(item);

      /*
       * display vacío restaura el valor definido por CSS
       * en lugar de imponer display:block.
       */
      item.style.display =
        shouldShow ? '' : 'none';
    });
  }


  /**
   * Guarda el rango seleccionado y vuelve a aplicar
   * todos los filtros.
   *
   * @return {void}
   */
  function applyDateFilter() {
    appliedStartDate = parseInputDate(
      startDateInput?.value || ''
    );

    appliedEndDate = parseInputDate(
      endDateInput?.value || ''
    );

    applyFilters();
  }


  /**
   * Restablece búsqueda y fechas.
   *
   * @return {void}
   */
  function resetFilters() {
    if (searchInput) {
      searchInput.value = '';
    }

    if (startDateInput) {
      startDateInput.value = '';
    }

    if (endDateInput) {
      endDateInput.value = '';
    }

    appliedStartDate = null;
    appliedEndDate = null;

    applyFilters();
  }


  /*
   * Búsqueda de texto.
   *
   * Conservamos los 300 ms del código existente para
   * no ejecutar el filtro en cada pulsación inmediatamente.
   */
  if (searchInput) {
    searchInput.addEventListener(
      'input',
      debounce(
        applyFilters,
        SEARCH_DELAY
      )
    );
  }


  /*
   * Aplicar rango de fechas.
   */
  if (
    thesesContainer &&
    applyDateButton
  ) {
    applyDateButton.addEventListener(
      'click',
      applyDateFilter
    );
  }


  /*
   * Reset único para todos los filtros.
   */
  if (resetButton) {
    resetButton.addEventListener(
      'click',
      resetFilters
    );
  }
}


initContentFilters();

/*=========================================================================
 * ICS calendar download
 *
 * Genera un archivo .ics a partir de los datos visibles
 * en la página de un evento.
 *
 * Soporta:
 *
 * 1. Fecha simple con hora.
 * 2. Fecha simple sin hora (all-day).
 * 3. Rango de fechas con hora.
 * 4. Rango de fechas sin hora (all-day).
 *
 * Fecha simple:
 *   #event-date .et_pb_module_header span
 *   19/07/2026
 *
 * Rango:
 *   #event-range-date .et_pb_blurb_description
 *   19/07/2026 – 24/07/2026
 *=========================================================================*/

function initICSDownload() {
  const DOWNLOAD_BUTTON_ID = 'add-to-calendar-button';

  const EVENT_DURATION_MINUTES = 60;

  const SELECTORS = {
    title:
      '#event-title .entry-title',

    singleDate:
      '#event-date .et_pb_module_header span',

    dateRange:
      '#event-range-date .et_pb_blurb_description',

    time:
      '#event-time .et_pb_module_header span',

    place:
      '#event-place .et_pb_module_header span'
  };


  const downloadButton =
    document.getElementById(
      DOWNLOAD_BUTTON_ID
    );

  if (!downloadButton) {
    return;
  }


  /**
   * Obtiene el texto de un elemento.
   *
   * @param {string} selector
   * @return {string}
   */
  function getElementText(selector) {
    const element =
      document.querySelector(selector);

    return element
      ? element.textContent.trim()
      : '';
  }


  /**
   * Obtiene la fecha o rango de fechas.
   *
   * Si existe un rango tiene prioridad sobre
   * la fecha simple.
   *
   * @return {{
   *   value: string,
   *   isRange: boolean
   * }}
   */
  function getEventDateData() {
    const rangeValue =
      getElementText(
        SELECTORS.dateRange
      );

    if (rangeValue) {
      return {
        value: rangeValue,
        isRange: true
      };
    }

    return {
      value:
        getElementText(
          SELECTORS.singleDate
        ),

      isRange: false
    };
  }


  /**
   * Obtiene los datos visibles del evento.
   *
   * @return {Object}
   */
  function getEventData() {
    const dateData =
      getEventDateData();

    return {
      title:
        getElementText(
          SELECTORS.title
        ),

      date:
        dateData.value,

      isDateRange:
        dateData.isRange,

      time:
        getElementText(
          SELECTORS.time
        ),

      place:
        getElementText(
          SELECTORS.place
        )
    };
  }


  /**
   * Valida que una fecha exista realmente.
   *
   * @param {number} year
   * @param {number} month
   * @param {number} day
   *
   * @return {boolean}
   */
  function isValidCalendarDate(
    year,
    month,
    day
  ) {
    const date = new Date(
      year,
      month - 1,
      day
    );

    return (
      date.getFullYear() === year &&
      date.getMonth() === month - 1 &&
      date.getDate() === day
    );
  }


  /**
   * Convierte DD/MM/YYYY en componentes.
   *
   * @param {string} value
   *
   * @return {{
   *   day: number,
   *   month: number,
   *   year: number
   * }|null}
   */
  function parseEventDate(value) {
    if (!value) {
      return null;
    }

    const match = value
      .trim()
      .match(
        /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/
      );

    if (!match) {
      return null;
    }


    const day =
      Number(match[1]);

    const month =
      Number(match[2]);

    const year =
      Number(match[3]);


    if (
      !isValidCalendarDate(
        year,
        month,
        day
      )
    ) {
      return null;
    }


    return {
      day,
      month,
      year
    };
  }


  /**
   * Convierte una fecha en un número comparable.
   *
   * 19/07/2026 → 20260719
   *
   * @param {Object} dateParts
   * @return {number}
   */
  function createDateKey(dateParts) {
    return (
      dateParts.year * 10000 +
      dateParts.month * 100 +
      dateParts.day
    );
  }


  /**
   * Parsea un rango de fechas.
   *
   * Admite:
   *
   * 19/07/2026 – 24/07/2026
   * 19/07/2026 - 24/07/2026
   * 19/07/2026 — 24/07/2026
   *
   * @param {string} value
   *
   * @return {{
   *   start: Object,
   *   end: Object
   * }|null}
   */
  function parseEventDateRange(value) {
    if (!value) {
      return null;
    }

    const parts = value
      .trim()
      .split(
        /\s*[–—-]\s*/
      );

    if (parts.length !== 2) {
      return null;
    }


    const start =
      parseEventDate(
        parts[0]
      );

    const end =
      parseEventDate(
        parts[1]
      );


    if (!start || !end) {
      return null;
    }


    if (
      createDateKey(end) <
      createDateKey(start)
    ) {
      return null;
    }


    return {
      start,
      end
    };
  }


  /**
   * Normaliza fecha simple y rango a una estructura
   * común start/end.
   *
   * @param {Object} eventData
   *
   * @return {{
   *   start: Object,
   *   end: Object,
   *   isRange: boolean
   * }|null}
   */
  function parseEventDates(
    eventData
  ) {
    if (
      eventData.isDateRange
    ) {
      const range =
        parseEventDateRange(
          eventData.date
        );

      if (!range) {
        return null;
      }

      return {
        start: range.start,
        end: range.end,
        isRange: true
      };
    }


    const date =
      parseEventDate(
        eventData.date
      );

    if (!date) {
      return null;
    }


    return {
      start: date,
      end: date,
      isRange: false
    };
  }


  /**
   * Convierte una hora a formato 24 horas.
   *
   * Admite:
   *
   * 12:00 pm
   * 12:00pm
   * 09:30 am
   * 14:30
   *
   * @param {string} value
   *
   * @return {{
   *   hour: number,
   *   minute: number
   * }|null}
   */
  function parseEventTime(value) {
    if (!value) {
      return null;
    }


    const match = value
      .trim()
      .toLowerCase()
      .match(
        /^(\d{1,2}):(\d{2})\s*(am|pm)?$/
      );


    if (!match) {
      return null;
    }


    let hour =
      Number(match[1]);

    const minute =
      Number(match[2]);

    const modifier =
      match[3] || '';


    if (
      minute < 0 ||
      minute > 59
    ) {
      return null;
    }


    if (modifier) {

      if (
        hour < 1 ||
        hour > 12
      ) {
        return null;
      }


      if (
        modifier === 'pm' &&
        hour !== 12
      ) {
        hour += 12;
      }


      if (
        modifier === 'am' &&
        hour === 12
      ) {
        hour = 0;
      }

    } else {

      if (
        hour < 0 ||
        hour > 23
      ) {
        return null;
      }
    }


    return {
      hour,
      minute
    };
  }


  /**
   * Crea una fecha/hora local.
   *
   * @param {Object} dateParts
   * @param {Object} timeParts
   *
   * @return {Date}
   */
  function createEventDateTime(
    dateParts,
    timeParts
  ) {
    return new Date(
      dateParts.year,
      dateParts.month - 1,
      dateParts.day,
      timeParts.hour,
      timeParts.minute,
      0
    );
  }


  /**
   * Formatea DATE-TIME para ICS en UTC.
   *
   * YYYYMMDDTHHMMSSZ
   *
   * @param {Date} date
   * @return {string}
   */
  function formatDateTimeToICS(date) {
    const pad = (number) =>
      String(number).padStart(2, '0');

    return (
      String(date.getUTCFullYear()) +
      pad(date.getUTCMonth() + 1) +
      pad(date.getUTCDate()) +
      'T' +
      pad(date.getUTCHours()) +
      pad(date.getUTCMinutes()) +
      pad(date.getUTCSeconds()) +
      'Z'
    );
  }


  /**
   * Formatea una fecha sin hora para ICS.
   *
   * YYYYMMDD
   *
   * @param {Object} dateParts
   * @return {string}
   */
  function formatDateOnlyToICS(
    dateParts
  ) {
    const pad = (number) =>
      String(number).padStart(2, '0');

    return (
      String(dateParts.year) +
      pad(dateParts.month) +
      pad(dateParts.day)
    );
  }


  /**
   * Añade días a una fecha sin introducir problemas
   * de zona horaria.
   *
   * @param {Object} dateParts
   * @param {number} days
   *
   * @return {Object}
   */
  function addDaysToDate(
    dateParts,
    days
  ) {
    const date = new Date(
      Date.UTC(
        dateParts.year,
        dateParts.month - 1,
        dateParts.day + days
      )
    );

    return {
      year:
        date.getUTCFullYear(),

      month:
        date.getUTCMonth() + 1,

      day:
        date.getUTCDate()
    };
  }


  /**
   * Crea el periodo de un evento con hora.
   *
   * @param {Object} dates
   * @param {Object} timeParts
   *
   * @return {{
   *   start: Date,
   *   end: Date
   * }}
   */
  function createTimedEventPeriod(
    dates,
    timeParts
  ) {
    const start =
      createEventDateTime(
        dates.start,
        timeParts
      );


    /*
     * Evento de un día:
     * duración estándar de 60 minutos.
     */
    if (!dates.isRange) {
      return {
        start,

        end: new Date(
          start.getTime() +
          EVENT_DURATION_MINUTES *
            60 *
            1000
        )
      };
    }


    /*
     * Evento de varios días con una única hora
     * disponible.
     *
     * Se utiliza esa hora en el día final y se añade
     * la duración estándar.
     */
    const finalDay =
      createEventDateTime(
        dates.end,
        timeParts
      );


    return {
      start,

      end: new Date(
        finalDay.getTime() +
        EVENT_DURATION_MINUTES *
          60 *
          1000
      )
    };
  }


  /**
   * Escapa caracteres especiales ICS.
   *
   * @param {string} text
   * @return {string}
   */
  function escapeICSText(
    text = ''
  ) {
    return String(text)
      .replace(
        /\\/g,
        '\\\\'
      )
      .replace(
        /\r?\n/g,
        '\\n'
      )
      .replace(
        /,/g,
        '\\,'
      )
      .replace(
        /;/g,
        '\\;'
      );
  }


  /**
   * Genera un UID.
   *
   * @return {string}
   */
  function createICSUID() {
    const uniquePart =
      typeof crypto !==
        'undefined' &&
      typeof crypto.randomUUID ===
        'function'

        ? crypto.randomUUID()

        : `${Date.now()}-${Math.random()
            .toString(36)
            .slice(2)}`;


    return (
      `${uniquePart}@${window.location.hostname}`
    );
  }


  /**
   * Construye las líneas DTSTART/DTEND de un
   * evento con hora.
   *
   * @param {Object} dates
   * @param {Object} timeParts
   *
   * @return {string[]}
   */
  function buildTimedDateLines(
    dates,
    timeParts
  ) {
    const period =
      createTimedEventPeriod(
        dates,
        timeParts
      );


    if (
      Number.isNaN(
        period.start.getTime()
      ) ||
      Number.isNaN(
        period.end.getTime()
      )
    ) {
      return [];
    }


    return [
      `DTSTART:${formatDateTimeToICS(
        period.start
      )}`,

      `DTEND:${formatDateTimeToICS(
        period.end
      )}`
    ];
  }


  /**
   * Construye DTSTART/DTEND de un evento sin hora.
   *
   * DTEND en eventos DATE es exclusivo.
   *
   * Por ejemplo:
   *
   * Evento 19/07/2026 – 24/07/2026
   *
   * DTSTART;VALUE=DATE:20260719
   * DTEND;VALUE=DATE:20260725
   *
   * @param {Object} dates
   *
   * @return {string[]}
   */
  function buildAllDayDateLines(
    dates
  ) {
    const exclusiveEnd =
      addDaysToDate(
        dates.end,
        1
      );


    return [
      `DTSTART;VALUE=DATE:${formatDateOnlyToICS(
        dates.start
      )}`,

      `DTEND;VALUE=DATE:${formatDateOnlyToICS(
        exclusiveEnd
      )}`
    ];
  }


  /**
   * Construye el contenido ICS.
   *
   * @param {Object} eventData
   * @param {Object} dates
   * @param {Object|null} timeParts
   *
   * @return {string}
   */
  function buildICSContent(
    eventData,
    dates,
    timeParts
  ) {
    /*
     * Si existe una hora válida generamos DATE-TIME.
     *
     * Si no hay hora, generamos un evento all-day.
     */
    const dateLines =
      timeParts
        ? buildTimedDateLines(
            dates,
            timeParts
          )
        : buildAllDayDateLines(
            dates
          );


    if (!dateLines.length) {
      return '';
    }


    const lines = [
      'BEGIN:VCALENDAR',
      'VERSION:2.0',
      'PRODID:-//CIMNE//Events//EN',
      'CALSCALE:GREGORIAN',

      'BEGIN:VEVENT',

      `UID:${createICSUID()}`,

      `DTSTAMP:${formatDateTimeToICS(
        new Date()
      )}`,

      ...dateLines,

      `SUMMARY:${escapeICSText(
        eventData.title
      )}`,

      `LOCATION:${escapeICSText(
        eventData.place
      )}`,

      'END:VEVENT',
      'END:VCALENDAR'
    ];


    return lines.join('\r\n');
  }


  /**
   * Genera un nombre de fichero seguro.
   *
   * @param {string} title
   * @return {string}
   */
  function createICSFilename(
    title
  ) {
    const safeTitle =
      (title || 'evento')
        .trim()
        .replace(
          /\s+/g,
          '_'
        )
        .replace(
          /[<>:"/\\|?*\u0000-\u001F]/g,
          ''
        );


    return (
      `${safeTitle || 'evento'}.ics`
    );
  }


  /**
   * Descarga el fichero.
   *
   * @param {string} content
   * @param {string} filename
   *
   * @return {void}
   */
  function downloadICSFile(
    content,
    filename
  ) {
    const blob = new Blob(
      [content],
      {
        type:
          'text/calendar;charset=utf-8'
      }
    );


    const objectUrl =
      URL.createObjectURL(blob);


    const link =
      document.createElement('a');


    link.href =
      objectUrl;

    link.download =
      filename;


    document.body.append(link);

    link.click();

    link.remove();


    window.setTimeout(() => {
      URL.revokeObjectURL(
        objectUrl
      );
    }, 0);
  }


  /**
   * Gestiona la descarga del calendario.
   *
   * @param {Event} event
   * @return {void}
   */
  function handleICSDownload(
    event
  ) {
    event.preventDefault();


    const eventData =
      getEventData();


    const dates =
      parseEventDates(
        eventData
      );


    if (!dates) {
      console.error(
        'Fecha de evento inválida:',
        eventData.date
      );

      window.alert(
        'Formato de fecha inválido.'
      );

      return;
    }


    /*
     * Una hora vacía es válida:
     * significa que el evento es de día completo.
     *
     * Solo mostramos error si existe texto de hora
     * pero su formato no es válido.
     */
    const hasTime =
      eventData.time !== '';


    const timeParts =
      hasTime
        ? parseEventTime(
            eventData.time
          )
        : null;


    if (
      hasTime &&
      !timeParts
    ) {
      console.error(
        'Hora de evento inválida:',
        eventData.time
      );

      window.alert(
        'Formato de hora inválido. Usa hh:mm am/pm (ej. 12:00 pm).'
      );

      return;
    }


    const icsContent =
      buildICSContent(
        eventData,
        dates,
        timeParts
      );


    if (!icsContent) {
      console.error(
        'No se pudo generar el archivo ICS.'
      );

      window.alert(
        'No se pudo generar el calendario del evento.'
      );

      return;
    }


    downloadICSFile(
      icsContent,

      createICSFilename(
        eventData.title
      )
    );
  }


  downloadButton.addEventListener(
    'click',
    handleICSDownload
  );
}


initICSDownload();
