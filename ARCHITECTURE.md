# Arquitectura del proyecto Bufete Melián

> Las convenciones para la IA están en `.cursor/rules/` y `.cursor/skills/`.
> Este documento es referencia rápida para desarrolladores.

## Stack

| Capa | Tecnología |
|------|-----------|
| Backend | Symfony 7 · PHP 8.2+ · Doctrine ORM |
| Frontend | React 18 · TypeScript · Tailwind CSS · TanStack Router & Query · shadcn/ui |
| Infra | Docker · WSL Ubuntu · `docker compose` (plugin v2) |

## Estructura del proyecto

```
bufete-app-marta/
├── src/                         # Backend — Arquitectura Hexagonal
│   ├── Domain/                  # Entidades, Value Objects, interfaces de repos
│   ├── Application/             # Use Cases, DTOs, Ports, Services
│   └── Infrastructure/          # Symfony Controllers, Doctrine, Voters, ApiClients
│
├── frontend/                    # App React
│   ├── src/routes/              # Stubs TanStack Router (finos, ~10 líneas)
│   ├── src/pages/               # Componentes de página (~150 líneas max)
│   ├── src/components/ui/       # Primitivos shadcn/ui
│   ├── src/components/<dominio>/ # Componentes por dominio
│   ├── src/components/layout/   # Sidebar, Topbar
│   ├── src/api/                 # Cliente API tipado
│   └── src/index.css            # Tokens CSS y @layer components
│
├── .cursor/
│   ├── rules/                   # Reglas Cursor (arquitectura, PHP, React, Docker)
│   └── skills/                  # Skills Cursor (tailwind-design-system)
│
├── config/                      # Configuración Symfony
├── docker-compose.yml
└── Dockerfile
```

## Principios clave

1. **Domain** nunca importa Infrastructure ni Application.
2. Controllers delgados: request → UseCase → response.
3. Repositorios: interfaz en `Domain\Repository`, implementación en `Infrastructure\Persistence\Doctrine`.
4. Frontend: token semánticos Tailwind, no hex hardcoded. Ver skill `tailwind-design-system`.
