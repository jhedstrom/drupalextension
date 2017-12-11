# language: es
@api @d7 @d8
Característica: Soporte para idiomas
  # Esta característica es copia traducida de la "feature" correspondiente
  # para demostrar que los correspondientes pasos están bien traducidos
  # al español
  Para demostrar la integración de idiomas
  Como desarrollador de Behat Extension
  Necesito proveer casos de test para soporte de idiomas

  # Este escenario asume que existe una instalación limpia del perfil "standard"
  # y que el módulo "behat_test" del directorio "fixtures/" esta activo

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
