from fastapi import APIRouter

from app.api.routes_auth import router as auth_router
from app.api.routes_dashboard import router as dashboard_router
from app.api.routes_attachments import router as attachments_router
from app.api.routes_checklists import router as checklists_router
from app.api.routes_emulsion import router as emulsion_router
from app.api.routes_assets import router as assets_router
from app.api.routes_ativo_categorias import router as ativo_categorias_router
from app.api.routes_lubrificantes import router as lubrificantes_router
from app.api.routes_parts import router as parts_router
from app.api.routes_pontos_lubrificacao import router as pontos_lub_router
from app.api.routes_preventivas import router as preventivas_router
from app.api.routes_relatorios import router as relatorios_router
from app.api.routes_setores import router as setores_router
from app.api.routes_tag_defeitos import router as tag_defeitos_router
from app.api.routes_users import router as users_router
from app.api.routes_work_orders import router as work_orders_router
from app.api.routes_work_order_consolidation import router as work_order_consolidation_router
from app.api.routes_work_order_logs import router as work_order_logs_router
from app.api.routes_work_order_part_requests import router as work_order_part_requests_router
from app.api.routes_admin_backup import router as admin_backup_router
from app.api.routes_admin_agendador import router as admin_agendador_router

api_router = APIRouter()
api_router.include_router(auth_router)
api_router.include_router(dashboard_router)
api_router.include_router(work_orders_router)
api_router.include_router(work_order_consolidation_router)
api_router.include_router(work_order_logs_router)
api_router.include_router(work_order_part_requests_router)
api_router.include_router(users_router)
api_router.include_router(setores_router)
api_router.include_router(tag_defeitos_router)
api_router.include_router(assets_router)
api_router.include_router(ativo_categorias_router)
api_router.include_router(parts_router)
api_router.include_router(preventivas_router)
api_router.include_router(relatorios_router)
api_router.include_router(lubrificantes_router)
api_router.include_router(pontos_lub_router)
api_router.include_router(attachments_router)
api_router.include_router(checklists_router)
api_router.include_router(emulsion_router)
api_router.include_router(admin_backup_router)
api_router.include_router(admin_agendador_router)
