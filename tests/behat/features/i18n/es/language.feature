# language: es
Característica: Soporte para idiomas
  Como desarrollador
  Quiero habilitar y verificar múltiples idiomas
  Para poder probar la funcionalidad multilingüe del sitio

  # Este escenario asume que existe una instalación limpia del perfil "standard"
  # y que el módulo "behat_test" del directorio "fixtures/" esta activo

  @test-drupal @api
  Escenario: Habilita múltiples idiomas
    Dado que los siguientes idiomas estan disponibles:
      | languages |
      | en        |
      | fr        |
      | de        |
    Y estoy conectado como usuario con rol 'administrator'
    Cuando voy a "admin/config/regional/language"
    Entonces debo ver "English"
    Y debo ver "French"
    Y debo ver "German"
