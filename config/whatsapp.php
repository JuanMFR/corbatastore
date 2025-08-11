<?php
/**
 * Configuración de WhatsApp
 * Cambiar el número por el número real de WhatsApp Business
 */

// Configuración del número de WhatsApp
// Formato: Código de país + número sin espacios ni guiones
// Ejemplo para Argentina: 5491123456789
define('WHATSAPP_NUMBER', '5492665030600');

// Mensajes predefinidos
define('WHATSAPP_MSG_GENERAL', '¡Hola! Me interesa conocer más sobre los productos de Corbata Store 👟');
define('WHATSAPP_MSG_PRODUCT', '¡Hola! Me interesa este producto:');

/**
 * Generar URL de WhatsApp para un producto específico
 */
function getWhatsAppProductUrl($producto_nombre, $producto_precio, $marca_nombre = '') {
    $message = WHATSAPP_MSG_PRODUCT . "%0A%0A";
    $message .= "*" . urlencode($producto_nombre) . "*%0A";
    
    if (!empty($marca_nombre)) {
        $message .= "Marca: " . urlencode($marca_nombre) . "%0A";
    }
    
    $message .= "Precio: $" . number_format($producto_precio, 0, ',', '.') . "%0A%0A";
    $message .= "¿Podrías darme más información? 👟";
    
    return "https://wa.me/" . WHATSAPP_NUMBER . "?text=" . $message;
}

/**
 * Generar URL de WhatsApp general
 */
function getWhatsAppGeneralUrl($custom_message = null) {
    $message = $custom_message ?: WHATSAPP_MSG_GENERAL;
    return "https://wa.me/" . WHATSAPP_NUMBER . "?text=" . urlencode($message);
}
?>