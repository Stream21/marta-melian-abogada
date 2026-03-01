# Arquitectura del proyecto Bufete

## Visión general: Hexagonal (Ports & Adapters)

El backend sigue **Arquitectura Hexagonal pura**: el dominio y los casos de uso no dependen de Symfony, Doctrine ni de ningún framework. Toda la tecnología vive en Infrastructure.

---

## Estructura en `src/`

```
src/
├── Domain/                          # Núcleo: reglas de negocio puras
│   ├── Entity/                      # Entidades o Aggregates de dominio
│   ├── Repository/                  # Interfaces de repositorios (puertos)
│   ├── ValueObject/                 # Objetos de valor inmutables
│   ├── Exception/                   # Excepciones de dominio
│   └── Event/                       # Eventos de dominio (opcional)
│
├── Application/                     # Casos de uso y orquestación
│   ├── UseCase/                     # Casos de uso (Command / Query)
│   ├── DTO/                         # Data Transfer Objects (entrada/salida)
│   ├── Port/                        # Puertos (interfaces para infra)
│   └── Service/                     # Servicios de aplicación (orquestadores)
│
└── Infrastructure/                  # Adaptadores: Symfony, Doctrine, etc.
    ├── Symfony/
    │   ├── Controller/              # Controladores HTTP (delgados)
    │   ├── Voter/                   # Voters de seguridad
    │   └── Validator/               # Validadores (constraints, etc.)
    ├── Persistence/
    │   └── Doctrine/
    │       ├── Entity/              # Entidades Doctrine (mapeo DB)
    │       └── Repository/          # Implementaciones de interfaces Domain\Repository
    └── ApiClient/                   # Clientes HTTP externos (adaptadores)
```

---

## Reglas de oro

1. **Domain**: Sin `use` de Symfony, Doctrine ni HTTP. Solo PHP puro y tipos.
2. **Application**: Depende solo de Domain. Usa interfaces (Port/Repository) para lo que hace Infrastructure.
3. **Infrastructure**: Implementa las interfaces de Domain/Application; aquí vive Symfony, Doctrine, Voters, Controllers.
4. **Controllers**: Delgados. Solo reciben request, llaman a un Use Case o Application Service y devuelven respuesta (DTO/JSON).
5. **Seguridad**: Voters en `Infrastructure\Symfony\Voter` para decisiones de autorización.
6. **Repositorios**: La interfaz está en `Domain\Repository\*RepositoryInterface`; la implementación en `Infrastructure\Persistence\Doctrine\Repository\*Repository`.

---

## Ubicación del frontend (React)

**Recomendación: carpeta `frontend/` en la raíz del proyecto.**

```
Bufete/
├── src/                 # Backend Symfony (Arquitectura Hexagonal)
├── frontend/            # App React (TypeScript, Tailwind, TanStack Query)
├── config/
├── public/
├── docker-compose.yml
├── Dockerfile            # Backend
└── frontend/Dockerfile   # Opcional: build/serve del frontend
```

### Ventajas

- **Separación clara**: Backend y frontend son proyectos distintos; cada uno con su `package.json` / `composer.json`.
- **Docker**: Un servicio para PHP (Symfony) y otro para Node (React): build y dev. El backend puede servir el build estático desde `public/frontend` o un reverse proxy (nginx) puede servir `/` al frontend y `/api` al PHP.
- **Desarrollo**: En local, React en `localhost:5173` (Vite) y API en `localhost:80` o el puerto que expongas para PHP; CORS configurado en Symfony.

### Alternativa: frontend dentro de `public/`

No recomendado: mezcla build de Vite/React con el árbol de Symfony y complica despliegues. Mejor mantener `frontend/` en la raíz.

---

## Comandos (entorno Docker en Windows/WSL2)

Los comandos de consola que afectan al backend deben ejecutarse dentro del contenedor PHP:

```bash
docker compose exec php composer install
docker compose exec php bin/console cache:clear
docker compose exec php bin/console make:entity
# etc.
```

Para el frontend (cuando exista el servicio):

```bash
docker compose exec node npm install
docker compose exec node npm run build
# o en desarrollo
docker compose exec node npm run dev
```

---

## Próximos pasos sugeridos

1. Configurar `composer.json` y el autoload PSR-4 para los namespaces `App\Domain`, `App\Application`, `App\Infrastructure`.
2. Crear un primer agregado/entidad en Domain y su interfaz de repositorio.
3. Implementar el repositorio en Doctrine (Infrastructure).
4. Crear un Use Case en Application y un Controller delgado que lo invoque.
5. Inicializar el proyecto React en `frontend/` con TypeScript, Tailwind y TanStack Query.
