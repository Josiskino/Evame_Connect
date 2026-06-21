import '../../../domain/entity/user_entity.dart';

class UserModel {
  final int id;
  final String name;
  final String email;
  final String? telephone;
  final List<String> roles;
  final List<String> permissions;
  final List<String> views;
  final List<AbilityEntity> abilities;

  const UserModel({
    required this.id,
    required this.name,
    required this.email,
    this.telephone,
    this.roles = const [],
    this.permissions = const [],
    this.views = const [],
    this.abilities = const [],
  });

  static List<String> _stringList(dynamic v) =>
      (v as List?)?.map((e) => e.toString()).toList() ?? const [];

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] as int,
      name: json['name'] ?? '',
      email: json['email'] ?? '',
      telephone: json['telephone'],
      roles: _stringList(json['roles']),
      permissions: _stringList(json['permissions']),
      views: _stringList(json['views']),
      abilities: ((json['abilities'] as List?) ?? const [])
          .map((e) => AbilityEntity(
                action: (e as Map)['action']?.toString() ?? '',
                subject: e['subject']?.toString() ?? '',
              ))
          .toList(),
    );
  }

  UserEntity toEntity() => UserEntity(
        id: id,
        name: name,
        email: email,
        telephone: telephone,
        roles: roles,
        permissions: permissions,
        views: views,
        abilities: abilities,
      );
}
