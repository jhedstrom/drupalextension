# language: es
@blackbox
Característica: Test DrupalContext
  # Esta característica es copia traducida de la "feature" correspondiente
  # para demostrar que los correspondientes pasos están bien traducidos
  # al español
  Para probar el adecuado funcionamiento del contexto Drupal usando el driver "blackbox"
  Como desarrollador
  Necesito usar los pasos definidos en este contexto

  Escenario: Prueba la capacidad de encontrar un encabezado en una zona
    Dado estoy en la página de inicio
    Cuando hago click en "Download & Extend"
    Entonces debo ver el encabezado "Download" en la zona "content"

  Escenario: Hacer click en contenido de una zona
    Dado que estoy en la URL "community.html"
    Cuando hago click en "IRC" de la zona "content"
    Entonces debo ver "Page status" en "right sidebar"
      Y debo ver el enlace "Drupal News" en la zona "footer"

  Escenario: Ver contenido en una zona
    Dado estoy en la página de inicio
    Entonces debo ver "Build something amazing." en "left header"
      Y debo ver "Build something amazing." en la zona "left header"

  Escenario: Prueba la capacidad de buscar texto que no debe aparecer en una zona
    Dado estoy en la página de inicio
    Entonces no debo ver el texto "Proprietary software is cutting edge" en "left header"
      Y no debo ver el texto "Proprietary software is cutting edge" en la zona "left header"

  Escenario: Enviar un formulario de una zona
    Dado estoy en la página de inicio
    Cuando relleno el campo "Search…" con "Views" en la zona "navigation"
      Y pulso "Search" en la zona "navigation"
    Entonces debo ver el texto "Search again" en la zona "right sidebar"

  Escenario: Prueba que enlace que existe en una zona
    Dado estoy en la página de inicio
    Entonces no debo ver el enlace "This link should never exist in a default Drupal install" en "right header"

  Escenario: Encontrar un botón
    Dado estoy en la página de inicio
    Entonces debo ver el botón "Search"

  Escenario: Encontrar un botón en una zona
    Dado estoy en la página de inicio
    Entonces debo ver el botón "Search" en "navigation"
      Y debo ver el botón "Search" en la zona "navigation"

  Escenario: Encontrar un elemento en una zona
    Dado estoy en la página de inicio
    Entonces debo ver un elemento "h1" en "left header"
      Y debo ver un elemento "h1" en la zona "left header"

  Escenario: Comprobar que no existe un elemento en una zona
    Dado estoy en la página de inicio
    Entonces no debo ver un elemento "h1" en "footer"
      Y no debo ver un elemento "h1" en la zona "footer"

  Escenario: Comprobar que no existe una elemento con un texto en una zona
    Dado estoy en la página de inicio
    Entonces no debo ver "DotNetNuke" en un elemento "h1" en "left header"
      Y no debo ver "DotNetNuke" en un elemento "h1" en la zona "left header"

  Escenario: Encontrar un elemento con un atributo en una zona
    Dado estoy en la página de inicio
    Entonces debo ver un elemento "h1" con el atributo "id" igual a "site-name" en la zona "left header"

  Escenario: Encontrar un texto en un elemento con un atributo en una zona
    Dado estoy en la página de inicio
    Entonces debo ver "Drupal" en un elemento "h1" con atributo "id" igual a "site-name" en la zona "left header"
      Y debo ver "Drupal" en un elemento "h1" con atributo "id" igual a "site-name" en "left header"

  Escenario: Encontrar un elemento con una atributo determinado en una zona
    Dado que estoy en la URL "assertRegionElementAttribute.html"
    Entonces debo ver un elemento "div" con el atributo "class" igual a "class1" en la zona "left header"
      Y debo ver un elemento "div" con el atributo "class" igual a "class2" en "left header"
      Y debo ver un elemento "div" con el atributo "class" igual a "class3" en "left header"

  Escenario: Encontrar un texto en un elemento con un determinado estilo CSS en una zona
    Dado que estoy en la URL "assertRegionElementAttribute.html"
    Entonces debo ver "footer" en un elemento "p" con estilo CSS "color" igual a "red" en la zona "footer"
    Entonces debo ver "footer" en un elemento "p" con estilo CSS "color" igual a "red" en "footer"

  Escenario: Mensajes de error
    Dado que estoy en la URL "user.html"
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

  @javascript
  Escenario: El driver Zombie funciona adecuadamente
    Dado estoy en la página de inicio
    Cuando hago click en "Download & Extend"
    Entonces debo ver el enlace "Distributions"
