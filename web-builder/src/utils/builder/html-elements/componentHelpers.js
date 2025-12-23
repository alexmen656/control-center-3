//
//
//
const componentHelpers = [
  {
    html_code: `
<section>
  <header class="bg-white shadow-sm">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-16 justify-between">
        <div class="flex items-center">
          <!-- Logo container -->
          <div class="flex-shrink-0 flex items-center" id="page-builder-editor-editable-area" data-header-logo="true">
            <img class="h-8 w-auto" src="/logo/logo.png" alt="Your Company Logo" />
          </div>
          <!-- Navigation links -->
          <div class="hidden sm:ml-6 sm:flex sm:space-x-8" id="page-builder-editor-editable-area" data-header-links="true">
            <!-- Current: "border-indigo-500 text-gray-900", Default: "border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700" -->
            <a href="#" class="inline-flex items-center border-b-2 border-indigo-500 px-1 pt-1 text-sm font-medium text-gray-900" no-underline>Home</a>
            <a href="#" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 no-underline">Features</a>
            <a href="#" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 no-underline">Pricing</a>
            <a href="#" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 no-underline">About</a>
            <!-- Dropdown example -->
            <div class="relative inline-flex items-center dropdown-container">
              <button type="button" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 dropdown-trigger">
                Resources
                <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
              </button>
              <div class="dropdown-menu hidden absolute top-full left-0 z-10 mt-3 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Documentation</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Guides</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">API Reference</a>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Right side buttons/actions -->
        <div class="hidden sm:ml-6 sm:flex sm:items-center" id="page-builder-editor-editable-area" data-header-actions="true">
          <button type="button" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            Contact Us
          </button>
        </div>

        <!-- Mobile menu button -->
        <div class="flex items-center sm:hidden">
          <button type="button" class="mobile-menu-button inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
            <span class="sr-only">Open main menu</span>
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile menu, show/hide based on menu state -->
    <div class="sm:hidden mobile-menu hidden" id="page-builder-editor-editable-area" data-header-mobile="true">
      <div class="space-y-1 pb-3 pt-2">
        <!-- Current: "bg-indigo-50 border-indigo-500 text-indigo-700", Default: "border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700" -->
        <a href="#" class="block border-l-4 border-indigo-500 bg-indigo-50 py-2 pl-3 pr-4 text-base font-medium text-indigo-700">Home</a>
        <a href="#" class="block border-l-4 border-transparent py-2 pl-3 pr-4 text-base font-medium text-gray-500 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700">Features</a>
        <a href="#" class="block border-l-4 border-transparent py-2 pl-3 pr-4 text-base font-medium text-gray-500 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700">Pricing</a>
        <a href="#" class="block border-l-4 border-transparent py-2 pl-3 pr-4 text-base font-medium text-gray-500 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700">About</a>
        <a href="#" class="block border-l-4 border-transparent py-2 pl-3 pr-4 text-base font-medium text-gray-500 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700">Resources</a>
      </div>
    </div>
  </header>
</section>`,
    id: null,
    title: 'Customizable Header',
    icon: `
        <span class="material-symbols-outlined">
        menu
        </span>
        `,
  },
  {
    html_code: `
        <section>
        <div class="relative py-4">
        <div class="mx-auto max-w-7xl lg:px-4 px-2">
        <div>
        <p><strong>Text</strong></p><p>Tempus imperdiet nulla malesuada pellentesque elit eget gravida cum sociis. Erat nam at lectus urna duis convallis convallis. Congue mauris rhoncus aenean vel elit scelerisque. 
        Turpis tincidunt id aliquet risus feugiat in ante. Tincidunt dui ut ornare lectus sit. Ipsum dolor sit amet consectetur. Viverra ipsum nunc aliquet bibendum enim facilisis gravida neque convallis.<br><br>Dignissim sodales ut eu sem integer vitae justo eget magna. 
        Ac turpis egestas maecenas pharetra convallis. Mauris sit amet massa vitae. Ut tellus elementum sagittis vitae et. Sed risus ultricies tristique nulla aliquet enim tortor. Nunc mattis enim ut tellus elementum sagittis vitae et leo. Quis vel eros donec ac odio tempor. 
        Faucibus scelerisque eleifend donec pretium. <br><br>Adipiscing bibendum est ultricies integer quis auctor elit. Vestibulum morbi blandit cursus risus at ultrices mi tempus imperdiet. Id porta nibh venenatis cras sed felis eget. Gravida dictum fusce ut placerat orci nulla. 
        Consequat mauris nunc congue nisi vitae suscipit tellus mauris. <br><br></p><p><strong>List</strong></p><ul><li><p>Integer enim neque volutpat ac tincidunt vitae semper quis. Sit amet consectetur adipiscing elit pellentesque.</p></li><li><p>Urna cursus eget nunc scelerisque viverra. 
        Cursus metus aliquam eleifend mi in nulla posuere. Lobortis elementum nibh tellus molestie nunc non blandit massa.</p></li><li><p>Sodales ut eu sem integer vitae justo eget magna. Scelerisque felis imperdiet proin fermentum leo vel orci. Nunc id cursus metus aliquam eleifend.</p></li></ul>
        </div>
        </div>
        </div>
        </section>`,
    id: null,
    title: 'Text',
    icon: `
        <span class="material-symbols-outlined">
        text_fields
        </span>
        `,
  },
  {
    html_code: `<section><div class="relative py-4"><div class="mx-auto max-w-7xl lg:px-4 px-2"><div class="break-words"><h2>Consequat mauris nunc congue</h2></div></div></div></section>`,
    id: null,
    title: 'Header H2',
    icon: `
        <span class="material-symbols-outlined">
        format_h2
        </span>
        `,
  },
  {
    html_code: `<section><div class="relative py-4"><div class="mx-auto max-w-7xl lg:px-4 px-2"><div class="break-words"><h3>Consequat mauris nunc congue</h3></div></div></div></section>`,
    id: null,
    title: 'Header H3',
    icon: `
        <span class="material-symbols-outlined">
        format_h3
        </span>
        `,
  },
  {
    html_code: `
        <section>
        <div class="py-4">
        <div class="mx-auto max-w-7xl lg:px-4 px-2">
        <div id="youtube-video" class="w-full aspect-video bg-slate-100 border border-slate-200 rounded-xl lg:p-8 md:p-6 p-4">
     
        <iframe
        frameborder="0" 
        allowfullscreen
        class="w-full aspect-video bg-gray-600 border border-slate-800 rounded-xl"
        src="" 
        allow="accelerometer; autoplay; clipboard-write;" allowfullscreen>
        </iframe>
        </div>
        </div>
        </div>
        </section>`,
    id: null,
    title: 'YouTube Video',
    icon: `
        <span class="material-symbols-outlined">
        play_circle
        </span>
        `,
  },
  {
    html_code: `<section><div class="relative py-8"><div class="absolute inset-0 flex items-center" aria-hidden="true"><div class="w-full border-4 border-gray-800 leading-none"></div></div><div class="relative flex justify-start"></div></div></section>`,
    id: null,
    title: 'Break Divider',
    icon: `
        <span class="material-symbols-outlined">
        horizontal_rule
        </span>
        `,
  },
  {
    html_code: `<section>\n<div class=\"w-full md:pt-2 md:pb-2 pt-4 pb-4 lg:px-4 px-2\">\n<div class=\"mx-auto max-w-7xl\">\n<div id=\"linktree\"\nclass=\"border-2 border-gray-600 flex items-centre justify-start rounded-md font-medium text-black\">\n<p>\n<a target=\"_blank\" rel=\"noopener noreferrer nofollow\" href=\"https://www.google.com\">Link to landing page</a>\n</p>\n</div>\n</div>\n</div>\n</section>`,
    id: null,
    title: 'Link',
    icon: `
        <span class="material-symbols-outlined">
        horizontal_rule
        </span>
        `,
  },
  {
    html_code: `<section><div class="relative py-4"><div class="mx-auto max-w-7xl lg:px-4 px-2"><div class="break-words"><h1>Header</h1></div></div></div></section>`,
    id: null,
    title: 'Header',
    icon: `
        <span class="material-symbols-outlined">
        toolbar
        </span>
        `,
  },
  {
    html_code: `<section>
        <div class="bg-gray-800 py-12">
          <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
              <div>
                <h3 class="text-lg font-semibold text-white">Unternehmen</h3>
                <ul class="mt-4 space-y-2">
                  <li><a href="#" class="text-sm text-gray-300 hover:text-white">Über uns</a></li>
                  <li><a href="#" class="text-sm text-gray-300 hover:text-white">Karriere</a></li>
                  <li><a href="#" class="text-sm text-gray-300 hover:text-white">Partner</a></li>
                  <li><a href="#" class="text-sm text-gray-300 hover:text-white">Blog</a></li>
                </ul>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-white">Produkte</h3>
                <ul class="mt-4 space-y-2">
                  <li><a href="#" class="text-sm text-gray-300 hover:text-white">Features</a></li>
                  <li><a href="#" class="text-sm text-gray-300 hover:text-white">Preise</a></li>
                  <li><a href="#" class="text-sm text-gray-300 hover:text-white">Integrationen</a></li>
                  <li><a href="#" class="text-sm text-gray-300 hover:text-white">API</a></li>
                </ul>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-white">Ressourcen</h3>
                <ul class="mt-4 space-y-2">
                  <li><a href="#" class="text-sm text-gray-300 hover:text-white">Dokumentation</a></li>
                  <li><a href="#" class="text-sm text-gray-300 hover:text-white">Hilfe & Support</a></li>
                  <li><a href="#" class="text-sm text-gray-300 hover:text-white">Community</a></li>
                  <li><a href="#" class="text-sm text-gray-300 hover:text-white">Webinare</a></li>
                </ul>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-white">Kontakt</h3>
                <ul class="mt-4 space-y-2">
                  <li class="flex items-center text-sm text-gray-300">
                    <span class="material-symbols-outlined text-sm mr-2">mail</span>
                    kontakt@example.com
                  </li>
                  <li class="flex items-center text-sm text-gray-300">
                    <span class="material-symbols-outlined text-sm mr-2">phone</span>
                    +49 123 456789
                  </li>
                  <li class="flex items-center text-sm text-gray-300">
                    <span class="material-symbols-outlined text-sm mr-2">location_on</span>
                    Musterstraße 123, 12345 Berlin
                  </li>
                </ul>
              </div>
            </div>
            <div class="mt-12 border-t border-gray-700 pt-8">
              <p class="text-center text-sm text-gray-400">&copy; 2025 Ihre Firma. Alle Rechte vorbehalten.</p>
            </div>
          </div>
        </div>
      </section>`,
    id: null,
    title: 'Footer',
    icon: `
        <span class="material-symbols-outlined">
        line_end
        </span>
        `,
  },
  {
    html_code: `<section>
        <div class="bg-white py-12 sm:py-16">
          <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
              <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Kontaktieren Sie uns</h2>
              <p class="mt-2 text-lg leading-8 text-gray-600">Wir freuen uns auf Ihre Nachricht</p>
            </div>
            <form class="mx-auto mt-8 max-w-xl">
              <div class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">
                <div>
                  <label for="first-name" class="block text-sm font-semibold leading-6 text-gray-900">Vorname</label>
                  <div class="mt-2.5">
                    <input type="text" name="first-name" id="first-name" autocomplete="given-name" class="block w-full rounded-md border-0 px-3.5 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                  </div>
                </div>
                <div>
                  <label for="last-name" class="block text-sm font-semibold leading-6 text-gray-900">Nachname</label>
                  <div class="mt-2.5">
                    <input type="text" name="last-name" id="last-name" autocomplete="family-name" class="block w-full rounded-md border-0 px-3.5 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                  </div>
                </div>
                <div class="sm:col-span-2">
                  <label for="email" class="block text-sm font-semibold leading-6 text-gray-900">Email</label>
                  <div class="mt-2.5">
                    <input type="email" name="email" id="email" autocomplete="email" class="block w-full rounded-md border-0 px-3.5 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                  </div>
                </div>
                <div class="sm:col-span-2">
                  <label for="message" class="block text-sm font-semibold leading-6 text-gray-900">Nachricht</label>
                  <div class="mt-2.5">
                    <textarea name="message" id="message" rows="4" class="block w-full rounded-md border-0 px-3.5 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"></textarea>
                  </div>
                </div>
              </div>
              <div class="mt-8 flex justify-center">
                <button type="submit" class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Senden</button>
              </div>
            </form>
          </div>
        </div>
      </section>`,
    id: null,
    title: 'Kontaktformular',
    icon: `
        <span class="material-symbols-outlined">
        contact_page
        </span>
        `,
  },
  {
    html_code: `<section>
        <div class="bg-white py-12 sm:py-16">
          <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
              <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Unsere Preise</h2>
              <p class="mt-2 text-lg leading-8 text-gray-600">Wählen Sie das passende Paket für Ihre Bedürfnisse</p>
            </div>
            <div class="mx-auto mt-16 grid max-w-5xl grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
              <!-- Basic Plan -->
              <div class="rounded-2xl border border-gray-200 p-8 shadow-sm transition-all hover:shadow-lg">
                <h3 class="text-xl font-semibold text-gray-900">Basic</h3>
                <p class="mt-4 text-sm text-gray-500">Ideal für Einsteiger und kleine Projekte</p>
                <p class="mt-6 flex">
                  <span class="text-4xl font-bold tracking-tight text-gray-900">€29</span>
                  <span class="ml-1 mt-1 text-xl font-semibold text-gray-500">/monat</span>
                </p>
                <ul class="mt-8 space-y-4 text-sm">
                  <li class="flex items-center">
                    <span class="material-symbols-outlined text-green-500 mr-2">check</span>
                    <span>5 Projekte</span>
                  </li>
                  <li class="flex items-center">
                    <span class="material-symbols-outlined text-green-500 mr-2">check</span>
                    <span>10GB Speicherplatz</span>
                  </li>
                  <li class="flex items-center">
                    <span class="material-symbols-outlined text-green-500 mr-2">check</span>
                    <span>Email Support</span>
                  </li>
                </ul>
                <button class="mt-8 w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Jetzt starten</button>
              </div>

              <!-- Pro Plan -->
              <div class="rounded-2xl border border-gray-200 bg-gray-50 p-8 shadow-md transition-all hover:shadow-lg">
                <div class="flex items-center justify-between">
                  <h3 class="text-xl font-semibold text-gray-900">Pro</h3>
                  <div class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-600">Beliebt</div>
                </div>
                <p class="mt-4 text-sm text-gray-500">Perfekt für wachsende Teams und Unternehmen</p>
                <p class="mt-6 flex">
                  <span class="text-4xl font-bold tracking-tight text-gray-900">€79</span>
                  <span class="ml-1 mt-1 text-xl font-semibold text-gray-500">/monat</span>
                </p>
                <ul class="mt-8 space-y-4 text-sm">
                  <li class="flex items-center">
                    <span class="material-symbols-outlined text-green-500 mr-2">check</span>
                    <span>20 Projekte</span>
                  </li>
                  <li class="flex items-center">
                    <span class="material-symbols-outlined text-green-500 mr-2">check</span>
                    <span>50GB Speicherplatz</span>
                  </li>
                  <li class="flex items-center">
                    <span class="material-symbols-outlined text-green-500 mr-2">check</span>
                    <span>Prioritäts-Support</span>
                  </li>
                  <li class="flex items-center">
                    <span class="material-symbols-outlined text-green-500 mr-2">check</span>
                    <span>Erweiterte Funktionen</span>
                  </li>
                </ul>
                <button class="mt-8 w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Jetzt starten</button>
              </div>

              <!-- Enterprise Plan -->
              <div class="rounded-2xl border border-gray-200 p-8 shadow-sm transition-all hover:shadow-lg">
                <h3 class="text-xl font-semibold text-gray-900">Enterprise</h3>
                <p class="mt-4 text-sm text-gray-500">Für große Organisationen mit speziellen Anforderungen</p>
                <p class="mt-6 flex">
                  <span class="text-4xl font-bold tracking-tight text-gray-900">€199</span>
                  <span class="ml-1 mt-1 text-xl font-semibold text-gray-500">/monat</span>
                </p>
                <ul class="mt-8 space-y-4 text-sm">
                  <li class="flex items-center">
                    <span class="material-symbols-outlined text-green-500 mr-2">check</span>
                    <span>Unbegrenzte Projekte</span>
                  </li>
                  <li class="flex items-center">
                    <span class="material-symbols-outlined text-green-500 mr-2">check</span>
                    <span>250GB Speicherplatz</span>
                  </li>
                  <li class="flex items-center">
                    <span class="material-symbols-outlined text-green-500 mr-2">check</span>
                    <span>24/7 Support</span>
                  </li>
                  <li class="flex items-center">
                    <span class="material-symbols-outlined text-green-500 mr-2">check</span>
                    <span>Eigene Instanz</span>
                  </li>
                </ul>
                <button class="mt-8 w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Kontakt aufnehmen</button>
              </div>
            </div>
          </div>
        </div>
      </section>`,
    id: null,
    title: 'Preistabelle',
    icon: `
        <span class="material-symbols-outlined">
        payments
        </span>
        `,
  },
  {
    html_code: `<section>
        <div class="bg-white py-12 sm:py-16">
          <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
              <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Häufig gestellte Fragen</h2>
              <p class="mt-2 text-lg leading-8 text-gray-600">Hier finden Sie Antworten auf die wichtigsten Fragen</p>
            </div>
            <div class="mx-auto mt-10 max-w-3xl space-y-6 divide-y divide-gray-900/10">
              <div class="pt-6">
                <h3 class="text-lg font-semibold leading-7 text-gray-900">Was ist das besondere an Ihrem Produkt?</h3>
                <p class="mt-2 text-base leading-7 text-gray-600">Unser Produkt zeichnet sich durch seine intuitive Benutzeroberfläche, umfangreiche Funktionen und exzellente Leistung aus. Es wurde entwickelt, um alltägliche Aufgaben zu erleichtern und die Produktivität zu steigern.</p>
              </div>
              <div class="pt-6">
                <h3 class="text-lg font-semibold leading-7 text-gray-900">Wie beginne ich mit der Nutzung?</h3>
                <p class="mt-2 text-base leading-7 text-gray-600">Sie können sofort mit einem kostenlosen Konto beginnen. Registrieren Sie sich auf unserer Website, bestätigen Sie Ihre E-Mail und folgen Sie der einfachen Einrichtungsanleitung. Innerhalb weniger Minuten sind Sie einsatzbereit.</p>
              </div>
              <div class="pt-6">
                <h3 class="text-lg font-semibold leading-7 text-gray-900">Welche Zahlungsmethoden werden unterstützt?</h3>
                <p class="mt-2 text-base leading-7 text-gray-600">Wir akzeptieren alle gängigen Kreditkarten (Visa, MasterCard, American Express), PayPal und Banküberweisung. Für Unternehmen bieten wir auch die Möglichkeit der Rechnungszahlung an.</p>
              </div>
              <div class="pt-6">
                <h3 class="text-lg font-semibold leading-7 text-gray-900">Gibt es eine Geld-zurück-Garantie?</h3>
                <p class="mt-2 text-base leading-7 text-gray-600">Ja, wir bieten eine 30-Tage-Geld-zurück-Garantie. Wenn Sie mit unserem Service nicht zufrieden sind, erstatten wir Ihnen den vollen Betrag ohne Fragen zu stellen.</p>
              </div>
              <div class="pt-6">
                <h3 class="text-lg font-semibold leading-7 text-gray-900">Wie kontaktiere ich den Support?</h3>
                <p class="mt-2 text-base leading-7 text-gray-600">Unser Support-Team ist per E-Mail (support@example.com), Live-Chat auf unserer Website oder telefonisch unter +49 123 456789 während der Geschäftszeiten erreichbar. Premium-Kunden haben Zugang zu unserem 24/7-Support.</p>
              </div>
            </div>
          </div>
        </div>
      </section>`,
    id: null,
    title: 'FAQ',
    icon: `
        <span class="material-symbols-outlined">
        help
        </span>
        `,
  },
  {
    html_code: `<section>
        <div class="bg-white py-12 sm:py-16">
          <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
              <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Features</h2>
              <p class="mt-2 text-lg leading-8 text-gray-600">Alles was unsere Plattform zu bieten hat</p>
            </div>
            <div class="mx-auto mt-16 grid max-w-5xl grid-cols-1 gap-12 sm:grid-cols-2 lg:grid-cols-3">
              <!-- Feature 1 -->
              <div class="text-center">
                <div class="flex justify-center">
                  <div class="rounded-full bg-indigo-100 p-3">
                    <span class="material-symbols-outlined text-indigo-600">speed</span>
                  </div>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">Schnelle Leistung</h3>
                <p class="mt-2 text-sm text-gray-500">Unsere Plattform wurde für maximale Geschwindigkeit optimiert, damit Sie Zeit sparen und effizient arbeiten können.</p>
              </div>

              <!-- Feature 2 -->
              <div class="text-center">
                <div class="flex justify-center">
                  <div class="rounded-full bg-indigo-100 p-3">
                    <span class="material-symbols-outlined text-indigo-600">security</span>
                  </div>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">Höchste Sicherheit</h3>
                <p class="mt-2 text-sm text-gray-500">Ihre Daten sind bei uns sicher. Wir verwenden modernste Verschlüsselungstechnologien und regelmäßige Sicherheitsaudits.</p>
              </div>

              <!-- Feature 3 -->
              <div class="text-center">
                <div class="flex justify-center">
                  <div class="rounded-full bg-indigo-100 p-3">
                    <span class="material-symbols-outlined text-indigo-600">devices</span>
                  </div>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">Responsive Design</h3>
                <p class="mt-2 text-sm text-gray-500">Unsere Plattform funktioniert perfekt auf allen Geräten - vom Desktop bis zum Smartphone.</p>
              </div>

              <!-- Feature 4 -->
              <div class="text-center">
                <div class="flex justify-center">
                  <div class="rounded-full bg-indigo-100 p-3">
                    <span class="material-symbols-outlined text-indigo-600">extension</span>
                  </div>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">Erweiterbar</h3>
                <p class="mt-2 text-sm text-gray-500">Zahlreiche Integrationen und APIs ermöglichen die Anpassung an Ihre individuellen Anforderungen.</p>
              </div>

              <!-- Feature 5 -->
              <div class="text-center">
                <div class="flex justify-center">
                  <div class="rounded-full bg-indigo-100 p-3">
                    <span class="material-symbols-outlined text-indigo-600">support_agent</span>
                  </div>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">Support</h3>
                <p class="mt-2 text-sm text-gray-500">Unser Expertenteam steht Ihnen jederzeit zur Verfügung, um Ihnen bei Fragen oder Problemen zu helfen.</p>
              </div>

              <!-- Feature 6 -->
              <div class="text-center">
                <div class="flex justify-center">
                  <div class="rounded-full bg-indigo-100 p-3">
                    <span class="material-symbols-outlined text-indigo-600">analytics</span>
                  </div>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">Detaillierte Analysen</h3>
                <p class="mt-2 text-sm text-gray-500">Umfassende Einblicke und Datenvisualisierungen helfen Ihnen, fundierte Entscheidungen zu treffen.</p>
              </div>
            </div>
          </div>
        </div>
      </section>`,
    id: null,
    title: 'Feature-Liste',
    icon: `
        <span class="material-symbols-outlined">
        featured_play_list
        </span>
        `,
  },
  {
    html_code: `<section>
        <div class="bg-white py-12 sm:py-16">
          <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
              <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Unser Team</h2>
              <p class="mt-2 text-lg leading-8 text-gray-600">Die kreativen Köpfe hinter unserem Erfolg</p>
            </div>
            <div class="mx-auto mt-16 grid max-w-7xl grid-cols-1 gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-4">
              <!-- Team Member 1 -->
              <div class="text-center">
                <div class="mx-auto h-40 w-40 overflow-hidden rounded-full">
                  <img class="h-full w-full object-cover object-center" src="https://images.unsplash.com/photo-1519345182560-3f2917c472ef?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1374&q=80" alt="Max Mustermann" />
                </div>
                <h3 class="mt-6 text-lg font-semibold leading-7 tracking-tight text-gray-900">Max Mustermann</h3>
                <p class="text-sm leading-6 text-indigo-600">CEO & Founder</p>
                <p class="mt-2 text-sm leading-6 text-gray-500">15+ Jahre Erfahrung in der Branche. Visionär und strategischer Denker.</p>
                <ul class="mt-4 flex justify-center space-x-4">
                  <li>
                    <a href="#" class="text-gray-400 hover:text-gray-500">
                      <span class="sr-only">LinkedIn</span>
                      <span class="material-symbols-outlined">link</span>
                    </a>
                  </li>
                  <li>
                    <a href="#" class="text-gray-400 hover:text-gray-500">
                      <span class="sr-only">Twitter</span>
                      <span class="material-symbols-outlined">mail</span>
                    </a>
                  </li>
                </ul>
              </div>

              <!-- Team Member 2 -->
              <div class="text-center">
                <div class="mx-auto h-40 w-40 overflow-hidden rounded-full">
                  <img class="h-full w-full object-cover object-center" src="https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1374&q=80" alt="Lisa Schmidt" />
                </div>
                <h3 class="mt-6 text-lg font-semibold leading-7 tracking-tight text-gray-900">Lisa Schmidt</h3>
                <p class="text-sm leading-6 text-indigo-600">CTO</p>
                <p class="mt-2 text-sm leading-6 text-gray-500">Tech-Enthusiastin mit Fachwissen in KI und Cloud-Computing.</p>
                <ul class="mt-4 flex justify-center space-x-4">
                  <li>
                    <a href="#" class="text-gray-400 hover:text-gray-500">
                      <span class="sr-only">LinkedIn</span>
                      <span class="material-symbols-outlined">link</span>
                    </a>
                  </li>
                  <li>
                    <a href="#" class="text-gray-400 hover:text-gray-500">
                      <span class="sr-only">Twitter</span>
                      <span class="material-symbols-outlined">mail</span>
                    </a>
                  </li>
                </ul>
              </div>

              <!-- Team Member 3 -->
              <div class="text-center">
                <div class="mx-auto h-40 w-40 overflow-hidden rounded-full">
                  <img class="h-full w-full object-cover object-center" src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1374&q=80" alt="Thomas Weber" />
                </div>
                <h3 class="mt-6 text-lg font-semibold leading-7 tracking-tight text-gray-900">Thomas Weber</h3>
                <p class="text-sm leading-6 text-indigo-600">Head of Design</p>
                <p class="mt-2 text-sm leading-6 text-gray-500">Kreativer Kopf mit Leidenschaft für nutzerorientiertes Design.</p>
                <ul class="mt-4 flex justify-center space-x-4">
                  <li>
                    <a href="#" class="text-gray-400 hover:text-gray-500">
                      <span class="sr-only">LinkedIn</span>
                      <span class="material-symbols-outlined">link</span>
                    </a>
                  </li>
                  <li>
                    <a href="#" class="text-gray-400 hover:text-gray-500">
                      <span class="sr-only">Twitter</span>
                      <span class="material-symbols-outlined">mail</span>
                    </a>
                  </li>
                </ul>
              </div>

              <!-- Team Member 4 -->
              <div class="text-center">
                <div class="mx-auto h-40 w-40 overflow-hidden rounded-full">
                  <img class="h-full w-full object-cover object-center" src="https://images.unsplash.com/photo-1580489944761-15a19d654956?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1374&q=80" alt="Anna Müller" />
                </div>
                <h3 class="mt-6 text-lg font-semibold leading-7 tracking-tight text-gray-900">Anna Müller</h3>
                <p class="text-sm leading-6 text-indigo-600">Head of Marketing</p>
                <p class="mt-2 text-sm leading-6 text-gray-500">Expertin für digitale Marketingstrategien und Markenentwicklung.</p>
                <ul class="mt-4 flex justify-center space-x-4">
                  <li>
                    <a href="#" class="text-gray-400 hover:text-gray-500">
                      <span class="sr-only">LinkedIn</span>
                      <span class="material-symbols-outlined">link</span>
                    </a>
                  </li>
                  <li>
                    <a href="#" class="text-gray-400 hover:text-gray-500">
                      <span class="sr-only">Twitter</span>
                      <span class="material-symbols-outlined">mail</span>
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </section>`,
    id: null,
    title: 'Team',
    icon: `
        <span class="material-symbols-outlined">
        group
        </span>
        `,
  },
  {
    html_code: `<section>
        <div class="bg-white py-12 sm:py-16">
          <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="grid grid-cols-1 items-center gap-x-8 gap-y-16 lg:grid-cols-2">
              <div class="mx-auto w-full max-w-xl lg:mx-0">
                <h2 class="text-3xl font-bold tracking-tight text-gray-900">Über uns</h2>
                <p class="mt-6 text-lg leading-8 text-gray-600">
                  Seit unserer Gründung im Jahr 2015 sind wir bestrebt, innovative Lösungen zu entwickeln, die das Leben unserer Kunden erleichtern. Mit mehr als 10 Jahren Branchenerfahrung kombinieren wir technisches Know-how mit kreativen Ideen.
                </p>
                <p class="mt-6 text-lg leading-8 text-gray-600">
                  Unsere Mission ist es, qualitativ hochwertige Produkte zu liefern, die nicht nur funktional sind, sondern auch ästhetisch ansprechen. Wir glauben an kontinuierliche Verbesserung und investieren regelmäßig in Forschung und Entwicklung.
                </p>
                <div class="mt-10 flex items-center gap-x-6">
                  <a href="#" class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Mehr erfahren</a>
                  <a href="#" class="text-sm font-semibold leading-6 text-gray-900">Kontakt <span aria-hidden="true">→</span></a>
                </div>
              </div>
              <div class="mx-auto grid w-full max-w-xl grid-cols-2 items-center gap-y-12 sm:gap-y-14 lg:mx-0 lg:max-w-none lg:pl-8">
                <div class="space-y-8">
                  <div class="flex flex-col-reverse gap-y-3 border-l border-gray-900/10 pl-6">
                    <dt class="text-sm leading-6 text-gray-600">Zufriedene Kunden</dt>
                    <dd class="text-3xl font-semibold tracking-tight text-gray-900">15k+</dd>
                  </div>
                  <div class="flex flex-col-reverse gap-y-3 border-l border-gray-900/10 pl-6">
                    <dt class="text-sm leading-6 text-gray-600">Gegründet</dt>
                    <dd class="text-3xl font-semibold tracking-tight text-gray-900">2015</dd>
                  </div>
                </div>
                <div class="space-y-8">
                  <div class="flex flex-col-reverse gap-y-3 border-l border-gray-900/10 pl-6">
                    <dt class="text-sm leading-6 text-gray-600">Länder</dt>
                    <dd class="text-3xl font-semibold tracking-tight text-gray-900">12</dd>
                  </div>
                  <div class="flex flex-col-reverse gap-y-3 border-l border-gray-900/10 pl-6">
                    <dt class="text-sm leading-6 text-gray-600">Team</dt>
                    <dd class="text-3xl font-semibold tracking-tight text-gray-900">35+</dd>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>`,
    id: null,
    title: 'Über uns',
    icon: `
        <span class="material-symbols-outlined">
        info
        </span>
        `,
  },
  {
    html_code: `<section>
        <div class="bg-white py-12 sm:py-16">
          <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
              <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Kunden-Testimonials</h2>
              <p class="mt-2 text-lg leading-8 text-gray-600">Erfahren Sie, was unsere Kunden über uns sagen</p>
            </div>
            <div class="mx-auto mt-16 grid max-w-7xl grid-cols-1 gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
              <!-- Testimonial 1 -->
              <figure class="rounded-2xl bg-gray-50 p-8 text-sm leading-6">
                <blockquote class="text-gray-900">
                  <p>"Die Zusammenarbeit war von Anfang an professionell und zielführend. Das Team hat unsere Erwartungen übertroffen und ein Produkt geliefert, das perfekt zu unseren Bedürfnissen passt."</p>
                </blockquote>
                <figcaption class="mt-6 flex items-center gap-x-4">
                  <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                    <span class="material-symbols-outlined">person</span>
                  </div>
                  <div>
                    <div class="font-semibold text-gray-900">Maria Schneider</div>
                    <div class="text-gray-600">Marketing Director, TechCorp GmbH</div>
                  </div>
                </figcaption>
              </figure>

              <!-- Testimonial 2 -->
              <figure class="rounded-2xl bg-gray-50 p-8 text-sm leading-6">
                <blockquote class="text-gray-900">
                  <p>"Die Implementierung verlief reibungslos und der Support ist außergewöhnlich. Wir konnten unsere Effizienz um 40% steigern und gleichzeitig die Kosten senken."</p>
                </blockquote>
                <figcaption class="mt-6 flex items-center gap-x-4">
                  <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                    <span class="material-symbols-outlined">person</span>
                  </div>
                  <div>
                    <div class="font-semibold text-gray-900">Michael Wagner</div>
                    <div class="text-gray-600">CTO, InnovateX</div>
                  </div>
                </figcaption>
              </figure>

              <!-- Testimonial 3 -->
              <figure class="rounded-2xl bg-gray-50 p-8 text-sm leading-6">
                <blockquote class="text-gray-900">
                  <p>"Was mich besonders beeindruckt hat, ist die Benutzerfreundlichkeit der Plattform und die Anpassungsfähigkeit an unsere spezifischen Anforderungen. Ein echter Game-Changer für unser Unternehmen!"</p>
                </blockquote>
                <figcaption class="mt-6 flex items-center gap-x-4">
                  <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                    <span class="material-symbols-outlined">person</span>
                  </div>
                  <div>
                    <div class="font-semibold text-gray-900">Julia Becker</div>
                    <div class="text-gray-600">CEO, StartUp Solutions</div>
                  </div>
                </figcaption>
              </figure>
            </div>
          </div>
        </div>
      </section>`,
    id: null,
    title: 'Testimonials',
    icon: `
        <span class="material-symbols-outlined">
        format_quote
        </span>
        `,
  },
  {
    html_code: `<section>
        <div class="bg-indigo-600 py-16 sm:py-24">
          <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
              <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Newsletter abonnieren</h2>
              <p class="mt-2 text-lg leading-8 text-indigo-200">Bleiben Sie auf dem Laufenden mit unseren neuesten Updates und Angeboten.</p>
            </div>
            <form class="mx-auto mt-10 flex max-w-md gap-x-4">
              <label for="email-address" class="sr-only">Email-Adresse</label>
              <input id="email-address" name="email" type="email" autocomplete="email" required class="min-w-0 flex-auto rounded-md border-0 bg-white/5 px-3.5 py-2 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-inset focus:ring-white sm:text-sm sm:leading-6" placeholder="Ihre E-Mail eingeben">
              <button type="submit" class="flex-none rounded-md bg-white px-3.5 py-2.5 text-sm font-semibold text-indigo-600 shadow-sm hover:bg-indigo-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Abonnieren</button>
            </form>
            <p class="mt-4 text-center text-sm text-indigo-200">Wir respektieren Ihre Privatsphäre. Sie können sich jederzeit abmelden.</p>
          </div>
        </div>
      </section>`,
    id: null,
    title: 'Newsletter',
    icon: `
        <span class="material-symbols-outlined">
        mail
        </span>
        `,
  },
  {
    html_code: `<section>
        <div class="bg-white py-12 sm:py-16">
          <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="grid grid-cols-1 items-center gap-x-8 gap-y-16 lg:grid-cols-2">
              <div class="mx-auto w-full max-w-xl lg:mx-0">
                <h2 class="text-3xl font-bold tracking-tight text-gray-900">Willkommen bei [Firmenname]</h2>
                <p class="mt-6 text-lg leading-8 text-gray-600">
                  Ihr vertrauenswürdiger Partner für [Hauptdienstleistung/Produkt] in [Stadt/Region] seit [Jahr].
                </p>
                <p class="mt-6 text-lg leading-8 text-gray-600">
                  Wir bieten Ihnen qualitativ hochwertige Lösungen zu fairen Preisen. Unsere erfahrenen Mitarbeiter stehen Ihnen jederzeit mit Rat und Tat zur Seite.
                </p>
                <div class="mt-10 flex items-center gap-x-6">
                  <a href="#" class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Jetzt Kontakt aufnehmen</a>
                  <a href="#" class="text-sm font-semibold leading-6 text-gray-900">Mehr über uns <span aria-hidden="true">→</span></a>
                </div>
              </div>
              <div class="mx-auto w-full max-w-xl lg:mx-0">
                <img src="/public/placeholder_image.jpg" alt="Bild des Unternehmens" class="rounded-lg shadow-xl">
              </div>
            </div>
          </div>
        </div>
      </section>`,
    id: null,
    title: 'Lokale Firma Hero-Section',
    icon: `
        <span class="material-symbols-outlined">
        storefront
        </span>
        `,
  },
  {
    html_code: `<section>
        <div class="bg-white py-12 sm:py-16">
          <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
              <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Unsere Öffnungszeiten</h2>
              <p class="mt-2 text-lg leading-8 text-gray-600">Wir sind für Sie da</p>
            </div>
            <div class="mx-auto mt-10 max-w-3xl rounded-lg bg-gray-50 p-8 shadow-md">
              <div class="grid grid-cols-2 gap-y-4">
                <div class="text-lg font-medium">Montag:</div>
                <div class="text-lg">08:00 - 18:00 Uhr</div>
                <div class="text-lg font-medium">Dienstag:</div>
                <div class="text-lg">08:00 - 18:00 Uhr</div>
                <div class="text-lg font-medium">Mittwoch:</div>
                <div class="text-lg">08:00 - 18:00 Uhr</div>
                <div class="text-lg font-medium">Donnerstag:</div>
                <div class="text-lg">08:00 - 18:00 Uhr</div>
                <div class="text-lg font-medium">Freitag:</div>
                <div class="text-lg">08:00 - 18:00 Uhr</div>
                <div class="text-lg font-medium">Samstag:</div>
                <div class="text-lg">09:00 - 14:00 Uhr</div>
                <div class="text-lg font-medium">Sonntag:</div>
                <div class="text-lg">Geschlossen</div>
              </div>
              <div class="mt-8 text-center text-sm text-gray-500">
                <p>An Feiertagen gelten möglicherweise besondere Öffnungszeiten.</p>
                <p class="mt-2">Terminvereinbarungen außerhalb der Öffnungszeiten nach Absprache möglich.</p>
              </div>
            </div>
          </div>
        </div>
      </section>`,
    id: null,
    title: 'Öffnungszeiten',
    icon: `
        <span class="material-symbols-outlined">
        schedule
        </span>
        `,
  },
  {
    html_code: `<section>
        <div class="bg-white py-12 sm:py-16">
          <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
              <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Unser Standort</h2>
              <p class="mt-2 text-lg leading-8 text-gray-600">Besuchen Sie uns</p>
            </div>
            <div class="mx-auto mt-10 max-w-5xl">
              <!-- Kartenplatzhalter - hier kann später ein echter Kartenservice eingebunden werden -->
              <div class="aspect-[16/9] w-full bg-gray-200 rounded-lg overflow-hidden">
                <div class="h-full w-full flex items-center justify-center">
                  <span class="text-gray-400">[Google Maps oder anderer Kartendienst]</span>
                </div>
              </div>
              
              <div class="mt-8 grid grid-cols-1 gap-8 sm:grid-cols-2">
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">Anschrift</h3>
                  <address class="mt-2 not-italic text-base text-gray-600">
                    [Firmenname]<br>
                    Musterstraße 123<br>
                    12345 Musterstadt<br>
                    Deutschland
                  </address>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">Kontakt</h3>
                  <div class="mt-2 text-base text-gray-600">
                    <p>Telefon: +49 123 456789</p>
                    <p>E-Mail: info@example.de</p>
                  </div>
                  <div class="mt-4">
                    <a href="#" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500">
                      Route planen
                      <span class="ml-1" aria-hidden="true">→</span>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>`,
    id: null,
    title: 'Standort/Anfahrt',
    icon: `
        <span class="material-symbols-outlined">
        location_on
        </span>
        `,
  },
  {
    html_code: `<section>
        <div class="bg-white py-12 sm:py-16">
          <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
              <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Unsere Leistungen</h2>
              <p class="mt-2 text-lg leading-8 text-gray-600">Was wir für Sie tun können</p>
            </div>
            <div class="mx-auto mt-16 grid max-w-5xl grid-cols-1 gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
              <!-- Service 1 -->
              <div>
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100">
                  <span class="material-symbols-outlined text-indigo-600">build</span>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">Service 1</h3>
                <p class="mt-2 text-base text-gray-600">Detaillierte Beschreibung Ihrer ersten Dienstleistung oder Ihres ersten Produkts. Erklären Sie den Nutzen und die Vorteile.</p>
                <a href="#" class="mt-4 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500">
                  Mehr erfahren
                  <span class="ml-1" aria-hidden="true">→</span>
                </a>
              </div>
              
              <!-- Service 2 -->
              <div>
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100">
                  <span class="material-symbols-outlined text-indigo-600">home_repair_service</span>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">Service 2</h3>
                <p class="mt-2 text-base text-gray-600">Detaillierte Beschreibung Ihrer zweiten Dienstleistung oder Ihres zweiten Produkts. Erklären Sie den Nutzen und die Vorteile.</p>
                <a href="#" class="mt-4 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500">
                  Mehr erfahren
                  <span class="ml-1" aria-hidden="true">→</span>
                </a>
              </div>
              
              <!-- Service 3 -->
              <div>
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100">
                  <span class="material-symbols-outlined text-indigo-600">handyman</span>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">Service 3</h3>
                <p class="mt-2 text-base text-gray-600">Detaillierte Beschreibung Ihrer dritten Dienstleistung oder Ihres dritten Produkts. Erklären Sie den Nutzen und die Vorteile.</p>
                <a href="#" class="mt-4 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500">
                  Mehr erfahren
                  <span class="ml-1" aria-hidden="true">→</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </section>`,
    id: null,
    title: 'Leistungen/Services',
    icon: `
        <span class="material-symbols-outlined">
        home_repair_service
        </span>
        `,
  },
  {
    html_code: `<section>
        <div class="bg-gray-100 py-12 sm:py-16">
          <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
              <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Aktuelle Angebote</h2>
              <p class="mt-2 text-lg leading-8 text-gray-600">Sichern Sie sich diese zeitlich begrenzten Angebote</p>
            </div>
            <div class="mx-auto mt-16 grid max-w-5xl grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
              <!-- Angebot 1 -->
              <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200 transition-all hover:shadow-md">
                <div class="p-6">
                  <div class="flex justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Frühjahrsangebot</h3>
                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">-15%</span>
                  </div>
                  <p class="mt-4 text-sm text-gray-600">Profitieren Sie von unserem Frühjahrsangebot und erhalten Sie 15% Rabatt auf alle Service-Leistungen.</p>
                  <div class="mt-4">
                    <p class="text-sm text-gray-500">Gültig bis: 31.05.2025</p>
                  </div>
                  <div class="mt-6">
                    <a href="#" class="inline-block rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Angebot nutzen</a>
                  </div>
                </div>
              </div>

              <!-- Angebot 2 -->
              <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200 transition-all hover:shadow-md">
                <div class="p-6">
                  <div class="flex justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">2 zum Preis von 1</h3>
                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">TOP-ANGEBOT</span>
                  </div>
                  <p class="mt-4 text-sm text-gray-600">Kaufen Sie ein Produkt und erhalten Sie ein zweites gratis dazu. Nur solange der Vorrat reicht!</p>
                  <div class="mt-4">
                    <p class="text-sm text-gray-500">Gültig bis: 15.06.2025</p>
                  </div>
                  <div class="mt-6">
                    <a href="#" class="inline-block rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Angebot nutzen</a>
                  </div>
                </div>
              </div>

              <!-- Angebot 3 -->
              <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200 transition-all hover:shadow-md">
                <div class="p-6">
                  <div class="flex justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Neukunden-Rabatt</h3>
                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">-10%</span>
                  </div>
                  <p class="mt-4 text-sm text-gray-600">Als Neukunde erhalten Sie 10% Rabatt auf Ihren ersten Einkauf bei uns.</p>
                  <div class="mt-4">
                    <p class="text-sm text-gray-500">Unbegrenzt gültig</p>
                  </div>
                  <div class="mt-6">
                    <a href="#" class="inline-block rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Angebot nutzen</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>`,
    id: null,
    title: 'Aktuelle Angebote',
    icon: `
        <span class="material-symbols-outlined">
        percent
        </span>
        `,
  },
  {
    html_code: `<section>
        <div class="bg-white py-12 sm:py-16">
          <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
              <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Unsere Galerie</h2>
              <p class="mt-2 text-lg leading-8 text-gray-600">Einblicke in unsere Arbeit und unsere Räumlichkeiten</p>
            </div>
            <div class="mx-auto mt-16 grid max-w-5xl grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
              <!-- Bild 1 -->
              <div class="overflow-hidden rounded-lg">
                <img src="/public/placeholder_image.jpg" alt="Bild 1" class="h-64 w-full object-cover transition-transform duration-300 hover:scale-105">
              </div>
              
              <!-- Bild 2 -->
              <div class="overflow-hidden rounded-lg">
                <img src="/public/placeholder_image.jpg" alt="Bild 2" class="h-64 w-full object-cover transition-transform duration-300 hover:scale-105">
              </div>
              
              <!-- Bild 3 -->
              <div class="overflow-hidden rounded-lg">
                <img src="/public/placeholder_image.jpg" alt="Bild 3" class="h-64 w-full object-cover transition-transform duration-300 hover:scale-105">
              </div>
              
              <!-- Bild 4 -->
              <div class="overflow-hidden rounded-lg">
                <img src="/public/placeholder_image.jpg" alt="Bild 4" class="h-64 w-full object-cover transition-transform duration-300 hover:scale-105">
              </div>
              
              <!-- Bild 5 -->
              <div class="overflow-hidden rounded-lg">
                <img src="/public/placeholder_image.jpg" alt="Bild 5" class="h-64 w-full object-cover transition-transform duration-300 hover:scale-105">
              </div>
              
              <!-- Bild 6 -->
              <div class="overflow-hidden rounded-lg">
                <img src="/public/placeholder_image.jpg" alt="Bild 6" class="h-64 w-full object-cover transition-transform duration-300 hover:scale-105">
              </div>
            </div>
            
            <div class="mt-10 text-center">
              <a href="#" class="inline-block rounded-md bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Alle Bilder ansehen</a>
            </div>
          </div>
        </div>
      </section>`,
    id: null,
    title: 'Bildergalerie',
    icon: `
        <span class="material-symbols-outlined">
        photo_library
        </span>
        `,
  },
  {
    html_code: `<section>
        <div class="bg-white py-12 sm:py-16">
          <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
              <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Lokale Kundenbewertungen</h2>
              <p class="mt-2 text-lg leading-8 text-gray-600">Was unsere Kunden aus [Ihre Stadt] über uns sagen</p>
            </div>
            <div class="mx-auto mt-16 grid max-w-5xl grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
              <!-- Bewertung 1 -->
              <div class="rounded-lg bg-gray-50 p-6 text-center">
                <div class="flex justify-center">
                  <div class="flex">
                    <span class="material-symbols-outlined text-yellow-400">star</span>
                    <span class="material-symbols-outlined text-yellow-400">star</span>
                    <span class="material-symbols-outlined text-yellow-400">star</span>
                    <span class="material-symbols-outlined text-yellow-400">star</span>
                    <span class="material-symbols-outlined text-yellow-400">star</span>
                  </div>
                </div>
                <blockquote class="mt-4">
                  <p class="text-base text-gray-600">"Ich bin mit der Dienstleistung äußerst zufrieden. Schnell, professionell und zu einem fairen Preis. Kann ich nur weiterempfehlen!"</p>
                </blockquote>
                <div class="mt-4">
                  <p class="text-sm font-medium text-gray-900">Peter M. aus [Stadt]</p>
                  <p class="text-sm text-gray-500">Kunde seit 2023</p>
                </div>
              </div>

              <!-- Bewertung 2 -->
              <div class="rounded-lg bg-gray-50 p-6 text-center">
                <div class="flex justify-center">
                  <div class="flex">
                    <span class="material-symbols-outlined text-yellow-400">star</span>
                    <span class="material-symbols-outlined text-yellow-400">star</span>
                    <span class="material-symbols-outlined text-yellow-400">star</span>
                    <span class="material-symbols-outlined text-yellow-400">star</span>
                    <span class="material-symbols-outlined text-yellow-400">star</span>
                  </div>
                </div>
                <blockquote class="mt-4">
                  <p class="text-base text-gray-600">"Sehr kompetente Beratung und erstklassige Arbeit. Das Team ist freundlich und hat alle meine Fragen geduldig beantwortet."</p>
                </blockquote>
                <div class="mt-4">
                  <p class="text-sm font-medium text-gray-900">Sandra K. aus [Stadt]</p>
                  <p class="text-sm text-gray-500">Kundin seit 2022</p>
                </div>
              </div>

              <!-- Bewertung 3 -->
              <div class="rounded-lg bg-gray-50 p-6 text-center">
                <div class="flex justify-center">
                  <div class="flex">
                    <span class="material-symbols-outlined text-yellow-400">star</span>
                    <span class="material-symbols-outlined text-yellow-400">star</span>
                    <span class="material-symbols-outlined text-yellow-400">star</span>
                    <span class="material-symbols-outlined text-yellow-400">star</span>
                    <span class="material-symbols-outlined text-gray-300">star</span>
                  </div>
                </div>
                <blockquote class="mt-4">
                  <p class="text-base text-gray-600">"Gute Qualität und schnelle Umsetzung. Ein kleiner Verbesserungsvorschlag wäre eine bessere Kommunikation während des Projekts."</p>
                </blockquote>
                <div class="mt-4">
                  <p class="text-sm font-medium text-gray-900">Michael B. aus [Stadt]</p>
                  <p class="text-sm text-gray-500">Kunde seit 2024</p>
                </div>
              </div>
            </div>
            
            <div class="mt-10 text-center">
              <p class="text-base text-gray-600">Haben Sie bereits mit uns gearbeitet? Wir freuen uns über Ihre Bewertung!</p>
              <a href="#" class="mt-4 inline-block rounded-md bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Bewertung abgeben</a>
            </div>
          </div>
        </div>
      </section>`,
    id: null,
    title: 'Lokale Bewertungen',
    icon: `
        <span class="material-symbols-outlined">
        rate_review
        </span>
        `,
  },
  {
    html_code: `<section>
        <div class="bg-white py-8 sm:py-12">
          <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
              <h2 class="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl">Folgen Sie uns</h2>
              <p class="mt-2 text-base leading-8 text-gray-600">Bleiben Sie auf dem Laufenden über Neuigkeiten und Angebote</p>
            </div>
            <div class="mx-auto mt-8 flex max-w-md justify-center gap-x-6">
              <!-- Facebook -->
              <a href="#" class="text-gray-400 hover:text-gray-500">
                <span class="sr-only">Facebook</span>
                <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
                </svg>
              </a>

              <!-- Instagram -->
              <a href="#" class="text-gray-400 hover:text-gray-500">
                <span class="sr-only">Instagram</span>
                <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd" />
                </svg>
              </a>

              <!-- Twitter/X -->
              <a href="#" class="text-gray-400 hover:text-gray-500">
                <span class="sr-only">Twitter</span>
                <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                </svg>
              </a>

              <!-- YouTube -->
              <a href="#" class="text-gray-400 hover:text-gray-500">
                <span class="sr-only">YouTube</span>
                <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path fill-rule="evenodd" d="M19.812 5.418c.861.23 1.538.907 1.768 1.768C21.998 8.746 22 12 22 12s0 3.255-.418 4.814a2.504 2.504 0 0 1-1.768 1.768c-1.56.419-7.814.419-7.814.419s-6.255 0-7.814-.419a2.505 2.505 0 0 1-1.768-1.768C2 15.255 2 12 2 12s0-3.255.417-4.814a2.507 2.507 0 0 1 1.768-1.768C5.744 5 11.998 5 11.998 5s6.255 0 7.814.418ZM15.194 12 10 15V9l5.194 3Z" clip-rule="evenodd" />
                </svg>
              </a>
            </div>
          </div>
        </div>
      </section>`,
    id: null,
    title: 'Social Media Links',
    icon: `
        <span class="material-symbols-outlined">
        share
        </span>
        `,
  },
];

export default componentHelpers;
