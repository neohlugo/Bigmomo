# Instalación y Optimización de Slider Revolution para Testimonios

Instalar Slider Revolution para “3 fotos y 2 textos” suele añadir JS/CSS pesado y cargar el hero de forma menos eficiente. Esto impacta en:

- **LCP:** El slider (above-the-fold) tarda más en pintar por scripts/imágenes → sube el LCP.
- **INP:** Más JS y animaciones → peor respuesta a la interacción.
- **CLS:** Si no reserva altura/aspect-ratio → saltos de layout.

Además, suele empeorar TBT y FCP/Speed Index por recursos extra.

En SEO, no hay "penalización por plugin", pero sí por Core Web Vitals/UX: página más lenta, peor experiencia y menor conversión (sobre todo en móvil).

## Solución propuesta (autogestionable y sin castigar SEO)

### Backend (WordPress)
- Crear un tipo de contenido "Testimonios" (CPT) para que el cliente cargue y edite testimonios desde el admin (texto, nombre, cargo/empresa, foto).
- Marcar cuáles van en Home y en qué orden.

### Frontend
- En la Home, mostrar solo los destacados (3–6) y renderizarlos directamente desde WordPress (sin depender de JS para verlos).
- El marcado queda listo para usar Swiper si quieren el efecto slider, pero si el JS no carga, los testimonios igual se ven.

### Performance
- Imágenes con tamaño definido para evitar saltos.
- Lazy-load en las imágenes no críticas.

### Esqueleto HTML de una tarjeta (DOM) + enfoque CSS

```html
<!-- Una slide de Swiper (solo estructura DOM, sin JS) -->
<div class="swiper-slide">
  <article class="tcard" aria-label="Testimonio">
    <figure class="tcard__media">
      <img
        class="tcard__avatar"
        src="avatar.jpg"
        alt="Foto de Ana Pérez"
        width="64"
        height="64"
        loading="lazy"
        decoding="async"
      />
    </figure>

    <div class="tcard__content">
      <blockquote class="tcard__quote">
        “Implementamos la tienda en 3 semanas y mejoró la conversión sin afectar performance.”
      </blockquote>

      <footer class="tcard__footer">
        <p class="tcard__name">Ana Pérez</p>
        <p class="tcard__role">CMO, Marca X</p>
      </footer>
    </div>
  </article>
</div>
```

## Enfoque CSS (breve)
describiría usando BEM + Flexbox para la tarjeta:
dotcard { display:flex; gap:…; align-items:flex-start; }
avatar con tamaño fijo y border-radius:999px; object-fit:cover;
tcontenido flexible (.tcard__content { flex:1; })
y así queda mantenible, consistente y fácil de extender. Si necesitara alineaciones más complejas, usaría CSS Grid dentro de .tcard.
