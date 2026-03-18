# language: es
Característica: DrupalContext
  Como desarrollador
  Quiero usar definiciones de pasos específicas de Drupal
  Para poder probar enlaces en filas de tablas, roles de usuario y encabezados de región

  @test-drupal @api
  Escenario: Crear y conectarte como usuario
    Dado que estoy conectado como usuario con rol "authenticated user"
    Cuando hago click en "My account"
    Entonces debo ver el texto "Member for"

  @test-drupal @api @d10
  Escenario: Enlaces dentro de filas de tablas  (Drupal 10)
    Dado que estoy conectado como un "administrator"
    Cuando estoy en la URL "admin/structure/types"
    Y hago click en el enlace "Manage fields" de la fila "Article"
    Entonces debo estar en "admin/structure/types/manage/article/fields"
    Y debo ver el enlace "Create a new field"

  @test-drupal @api
  Escenario: Cear usuarios con roles
    Dados usuarios:
      | name     | mail             | roles         |
      | Joe User | joe@example.com  | Administrator |
      | Jane Doe | jane@example.com |               |
    Y estoy conectado como usuario con rol "administrator"
    Cuando visito "admin/people"
    Entonces debo ver el texto "Administrator" en la fila "Joe User"
    Y no debo ver el texto "administrator" en la fila "Jane Doe"

  @test-drupal @api
  Escenario: Encontrar un encabezado en una zona
    Dado que no estoy conectado
    Cuando estoy en la página de inicio
    Entonces debo ver el encabezado "Welcome!" en la zona "main content"

  # lo siguiente comprueba que un usuario creado por una clase Conexto (en este
  # caso FeatureContext::assertLoggedInByUsernameAndPassword()) puede ser utilizado
  # por otro Contexto (DrupalContext::assertLoggedInByName()).
  @test-drupal @api
  Escenario: Conectarse como usuario sin dirección de correo
    # notar que el siguiente paso no está traducido: está definido en FeatureContext (no en DrupalContext)
    Dado I am logged in as a user with name "Carrot Ironfoundersson" and password "citywatch1234"
    Entonces estoy conectado como "Carrot Ironfoundersson"
