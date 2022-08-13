# Pruebas de registro de clientes

No existe un servicio de integración para eliminar clientes registrados.

## Pruebas de AddService

No existe un método (manual o automatizado) para poder eliminar un cliente recién creado.
Por lo tanto, ejecutar el test de integración relacionado con la creación de un nuevo RFC
creará un nuevo registro que no hay forma de limpiar.

Por este motivo, el test de creación es siempre omitido. Lo puede reactivar estableciendo la
variable de entorno FINKOK_REGISTRATION_ADD_CREATE_RANDOM_RFC a "1".

El test implementado busca un RFC el prefijo `XDEL`.
Además, se usa el RFC `XDEL000101XX1` para diferentes pruebas y se espera que exista.
Este RFC está definido en `RegistrationIntegrationTestCase::CUSTOMER_RFC`.

Después puedes solicitar a Finkok que se eliminen estos RFC vía correo electrónico, porque sí.

## Pruebas de ObtainService

Se espera que el RFC ABCD010101AAA (que es inválido) no esté registrado.
Este RFC está definido en `RegistrationIntegrationTestCase::CUSTOMER_NON_EXISTENT`.

Se espera que el RFC de pruebas EKU9003173C9 esté registrado como ilimitado (CustomerType::onDemand())
Se espera que el RFC de pruebas EKU9003173C9 esté activo.

## Pruebas de Assign

Se espera que el RFC XDEL000101XX1 esté registrado.

La prueba consiste en obtener el registro actual, en marcarlo como ondemand,
cambiarlo a prepaid con 10 créditos, agregarle 15 créditos, dejarlo como ondemand.

## Pruebas de Edit

Se espera que el RFC ABCD010101AAA (que es inválido) no esté registrado.

Se espera que el RFC XDEL000101XX1 esté registrado.

La prueba consiste en obtener el registro actual, marcarlo como activo si no lo está,
cambiarlo a estado suspendido, cambiarlo a estado activo.
