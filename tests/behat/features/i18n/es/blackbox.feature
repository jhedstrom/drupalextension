# language: es
Característica: Test DrupalContext
  Como desarrollador
  Quiero interactuar con regiones y elementos de la página usando el driver blackbox
  Para poder probar el comportamiento de la interfaz sin acceso directo a la API de Drupal

  @test-blackbox
  Escenario: Prueba la capacidad de encontrar un encabezado en una zona
    Dado estoy en la página de inicio
    Cuando hago click en "Page Two"
    Entonces debo ver el encabezado "Download" en la zona "static content"

  @test-blackbox
  Escenario: Hacer click en contenido de una zona
    Dado que estoy en la URL "page_one.html"
    Cuando hago click en "Page Three" de la zona "static content"
    Entonces debo ver "Page status" en "static sidebar"
    Y debo ver el enlace "Home" en la zona "static footer"

  @test-blackbox
  Escenario: Ver contenido en una zona
    Dado estoy en la página de inicio
    Entonces debo ver "Welcome to the test site." en "static left header"
    Y debo ver "Welcome to the test site." en la zona "static left header"

  @test-blackbox
  Escenario: Prueba la capacidad de buscar texto que no debe aparecer en una zona
    Dado estoy en la página de inicio
    Entonces no debo ver el texto "Proprietary software is cutting edge" en "static left header"
    Y no debo ver el texto "Proprietary software is cutting edge" en la zona "static left header"

  @test-blackbox
  Escenario: Enviar un formulario de una zona
    Dado estoy en la página de inicio
    Cuando relleno el campo "Search…" con "Views" en la zona "static navigation"
    Y pulso "Search" en la zona "static navigation"
    Entonces debo ver el texto "Home" en la zona "static sidebar"

  @test-blackbox
  Escenario: Prueba que enlace que existe en una zona
    Dado estoy en la página de inicio
    Entonces no debo ver el enlace "This link should never exist in a default Drupal install" en "static right header"

  @test-blackbox
  Escenario: Encontrar un botón
    Dado estoy en la página de inicio
    Entonces debo ver el botón "Search"

  @test-blackbox
  Escenario: Encontrar un botón en una zona
    Dado estoy en la página de inicio
    Entonces debo ver el botón "Search" en "static navigation"
    Y debo ver el botón "Search" en la zona "static navigation"

  @test-blackbox
  Escenario: Encontrar un elemento en una zona
    Dado estoy en la página de inicio
    Entonces debo ver un elemento "h1" en "static left header"
    Y debo ver un elemento "h1" en la zona "static left header"

  @test-blackbox
  Escenario: Comprobar que no existe un elemento en una zona
    Dado estoy en la página de inicio
    Entonces no debo ver un elemento "h1" en "static footer"
    Y no debo ver un elemento "h1" en la zona "static footer"

  @test-blackbox
  Escenario: Comprobar que no existe una elemento con un texto en una zona
    Dado estoy en la página de inicio
    Entonces no debo ver "DotNetNuke" en un elemento "h1" en "static left header"
    Y no debo ver "DotNetNuke" en un elemento "h1" en la zona "static left header"

  @test-blackbox
  Escenario: Encontrar un elemento con un atributo en una zona
    Dado estoy en la página de inicio
    Entonces debo ver un elemento "h1" con el atributo "id" igual a "static-site-name" en la zona "static left header"

  @test-blackbox
  Escenario: Encontrar un texto en un elemento con un atributo en una zona
    Dado estoy en la página de inicio
    Entonces debo ver "Test Static Site" en un elemento "h1" con atributo "id" igual a "static-site-name" en la zona "static left header"
    Y debo ver "Test Static Site" en un elemento "h1" con atributo "id" igual a "static-site-name" en "static left header"

  @test-blackbox
  Escenario: Encontrar un elemento con una atributo determinado en una zona
    Dado que estoy en la URL "element_attributes.html"
    Entonces debo ver un elemento "div" con el atributo "class" igual a "class1" en la zona "static left header"
    Y debo ver un elemento "div" con el atributo "class" igual a "class2" en "static left header"
    Y debo ver un elemento "div" con el atributo "class" igual a "class3" en "static left header"

  @test-blackbox
  Escenario: Encontrar un texto en un elemento con un determinado estilo CSS en una zona
    Dado que estoy en la URL "element_attributes.html"
    Entonces debo ver "footer" en un elemento "p" con estilo CSS "color" igual a "red" en la zona "static footer"
    Entonces debo ver "footer" en un elemento "p" con estilo CSS "color" igual a "red" en "static footer"

  @test-blackbox
  Escenario: Mensajes de error
    Dado que estoy en la URL "form_page.html"
    Cuando presiono "Log in"
    Entonces debo ver el mensaje de error "Password field is required"
    Y debo ver el mensaje de error conteniendo "Password field is required"
    Y no debo ver el mensaje de error "Sorry, unrecognized username or password"
    Y no debo ver el mensaje de error conteniendo "Sorry, unrecognized username or password"
    Y debo ver los siguientes mensajes de error:
      | error messages                       |
      | Username or email field is required. |
      | Password field is required           |
    Y no debo ver los siguientes mensajes de error:
      | error messages                                                                |
      | Sorry, unrecognized username or password                                      |
      | Unable to send e-mail. Contact the site administrator if the problem persists |
