-- Dados FICTICIOS do APChat de teste (apchat_homologacao). NUNCA rodar no APChat oficial. Pode rodar de novo.
-- Tenant 21 = Curciol; tenant 99 = outra empresa (nao pode aparecer nas visoes da Curciol).
INSERT INTO public."Whatsapps" (id, name, type, status, "isActive", "isDeleted", "tenantId", "createdAt", "updatedAt") VALUES
 (112, 'Curciol - Oficial (teste)', 'waba', 'CONNECTED', true, false, 21, now(), now()),
 (113, 'Curciol - Coexistência (teste)', 'uazapi', 'CONNECTED', true, false, 21, now(), now()),
 (199, 'Outra empresa', 'waba', 'CONNECTED', true, false, 99, now(), now())
ON CONFLICT (id) DO NOTHING;

INSERT INTO public."Users" (id, name, email, profile, "tenantId", inactive, "createdAt", "updatedAt") VALUES
 (9001, 'Atendente Um', 'atendente.um@teste.local', 'admin', 21, false, now(), now()),
 (9002, 'Atendente Dois', 'atendente.dois@teste.local', 'user', 21, false, now(), now()),
 (9003, 'Atendente Tres', 'atendente.tres@teste.local', 'user', 21, false, now(), now()),
 (9099, 'Outra Empresa', 'outra@teste.local', 'admin', 99, false, now(), now())
ON CONFLICT (id) DO NOTHING;

INSERT INTO public."Queues" (id, queue, "isActive", "tenantId", "createdAt", "updatedAt") VALUES
 (9101, 'Fila Um', true, 21, now(), now()),
 (9102, 'Fila Dois', true, 21, now(), now()),
 (9199, 'Fila Outra', true, 99, now(), now())
ON CONFLICT (id) DO NOTHING;

INSERT INTO public."UsersQueues" (id, "userId", "queueId", "createdAt", "updatedAt") VALUES
 (9201, 9001, 9101, now(), now()), (9202, 9001, 9102, now(), now()), (9203, 9002, 9101, now(), now()), (9204, 9003, 9102, now(), now())
ON CONFLICT (id) DO NOTHING;

INSERT INTO public."UserWhatsapps" (id, "userId", "whatsappId", "isActive", "createdAt", "updatedAt") VALUES
 (9301, 9001, 112, true, now(), now()), (9302, 9002, 112, true, now(), now()), (9303, 9003, 112, true, now(), now())
ON CONFLICT (id) DO NOTHING;
