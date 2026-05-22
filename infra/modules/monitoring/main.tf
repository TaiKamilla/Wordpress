# Azure Monitor: Log Analytics Workspace + Application Insights + Diagnostic Settings + Dashboard.
# Wires the major resources (App Service, APIM, Front Door) into a single workspace
# so logs and metrics are queryable from one place.

resource "azurerm_log_analytics_workspace" "main" {
  name                = "log-${var.project}-${var.environment}"
  resource_group_name = var.resource_group_name
  location            = var.location
  sku                 = "PerGB2018"
  retention_in_days   = var.log_retention_days

  tags = var.tags
}

resource "azurerm_application_insights" "main" {
  name                = "appi-${var.project}-${var.environment}"
  resource_group_name = var.resource_group_name
  location            = var.location
  workspace_id        = azurerm_log_analytics_workspace.main.id
  application_type    = "web"

  tags = var.tags
}

# Diagnostic settings — pipe resource logs into the workspace
resource "azurerm_monitor_diagnostic_setting" "app_service" {
  name                       = "diag-${var.project}-${var.environment}-app"
  target_resource_id         = var.app_service_id
  log_analytics_workspace_id = azurerm_log_analytics_workspace.main.id

  enabled_log {
    category_group = "allLogs"
  }

  metric {
    category = "AllMetrics"
  }
}

resource "azurerm_monitor_diagnostic_setting" "apim" {
  name                       = "diag-${var.project}-${var.environment}-apim"
  target_resource_id         = var.apim_id
  log_analytics_workspace_id = azurerm_log_analytics_workspace.main.id

  enabled_log {
    category_group = "allLogs"
  }

  metric {
    category = "AllMetrics"
  }
}

resource "azurerm_monitor_diagnostic_setting" "front_door" {
  count = var.front_door_id != null ? 1 : 0

  name                       = "diag-${var.project}-${var.environment}-fd"
  target_resource_id         = var.front_door_id
  log_analytics_workspace_id = azurerm_log_analytics_workspace.main.id

  enabled_log {
    category_group = "allLogs"
  }

  metric {
    category = "AllMetrics"
  }
}

# Minimal dashboard. Expand the JSON definition with more tiles as needed —
# easiest path is to design it in the Azure Portal, then "Export template".
resource "azurerm_portal_dashboard" "main" {
  name                = "dash-${var.project}-${var.environment}"
  resource_group_name = var.resource_group_name
  location            = var.location
  tags                = var.tags

  dashboard_properties = jsonencode({
    lenses = {
      "0" = {
        order = 0
        parts = {
          "0" = {
            position = { x = 0, y = 0, colSpan = 6, rowSpan = 4 }
            metadata = {
              type = "Extension/HubsExtension/PartType/MarkdownPart"
              settings = {
                content = {
                  settings = {
                    content  = "# JTI ${title(var.environment)}\n\n[Log Analytics workspace](https://portal.azure.com/#resource${azurerm_log_analytics_workspace.main.id})\n\n[Application Insights](https://portal.azure.com/#resource${azurerm_application_insights.main.id})"
                    title    = "JTI"
                    subtitle = var.environment
                  }
                }
              }
            }
          }
        }
      }
    }
  })
}
